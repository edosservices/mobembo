<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\InvestmentStatus;
use App\Enums\KycStatus;
use App\Enums\LedgerStatus;
use App\Enums\LedgerType;
use App\Enums\ProjectStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Models\Deposit;
use App\Models\Investment;
use App\Models\LedgerEntry;
use App\Models\Project;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Support\Money;
use Illuminate\Support\Carbon;

class AdminOverviewService
{
    /**
     * @return array<string, int|string>
     */
    public function stats(): array
    {
        $clients = User::query()->where('role', UserRole::User);

        return [
            'users_total' => (clone $clients)->count(),
            'users_active' => (clone $clients)->where('status', AccountStatus::Active)->count(),
            'deposits_total' => Money::of(Deposit::query()->where('status', ReviewStatus::Approved)->sum('amount')),
            'deposits_pending' => Deposit::query()->where('status', ReviewStatus::Pending)->count(),
            'withdrawals_total' => Money::of(Withdrawal::query()->where('status', ReviewStatus::Approved)->sum('amount')),
            'withdrawals_pending' => Withdrawal::query()->where('status', ReviewStatus::Pending)->count(),
            'invested' => Money::of(Wallet::query()->sum('invested_balance')),
            'returns' => $this->ledgerSum(LedgerType::InvestmentReturn),
            'projects_open' => Project::query()->where('status', ProjectStatus::Open)->count(),
            'projects_active' => Project::query()->where('status', ProjectStatus::Active)->count(),
            'projects_finished' => Project::query()->whereIn('status', [ProjectStatus::Finished, ProjectStatus::Closed, ProjectStatus::Complete, ProjectStatus::Funded])->count(),
            'investments_active' => Investment::query()->where('status', InvestmentStatus::Active)->count(),
            'commissions' => $this->ledgerSum(LedgerType::ReferralCommission),
            'bonus' => $this->ledgerSum(LedgerType::Bonus),
        ];
    }

    /**
     * @return array{total: int, deposits: int, withdrawals: int, investments: int, kyc: int, support: int}
     */
    public function attention(): array
    {
        $deposits = Deposit::query()->where('status', ReviewStatus::Pending)->count();
        $withdrawals = Withdrawal::query()->where('status', ReviewStatus::Pending)->count();
        $investments = Investment::query()->where('status', InvestmentStatus::Suspended)->count();
        $kyc = User::query()->where('role', UserRole::User)->where('kyc_status', KycStatus::Pending)->count();

        return [
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
            'investments' => $investments,
            'kyc' => $kyc,
            'support' => 0,
            'total' => $deposits + $withdrawals + $investments + $kyc,
        ];
    }

    /**
     * @return array{empty: bool, range: string, labels: list<string>, deposits: string, withdrawals: string, investments: string}
     */
    public function activity(string $range): array
    {
        [$from, $days, $range] = $this->window($range);
        $deposits = $this->dailyTotals(Deposit::query()->where('status', ReviewStatus::Approved), 'reviewed_at', $from);
        $withdrawals = $this->dailyTotals(Withdrawal::query()->where('status', ReviewStatus::Approved), 'reviewed_at', $from);
        $investments = $this->dailyTotals(Investment::query(), 'invested_at', $from);
        $series = $this->fill($from, $days, [
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
            'investments' => $investments,
        ]);

        return [
            'empty' => $this->isEmpty($series, ['deposits', 'withdrawals', 'investments']),
            'range' => $range,
            'labels' => array_column($series, 'label'),
            'deposits' => $this->polyline($series, 'deposits'),
            'withdrawals' => $this->polyline($series, 'withdrawals'),
            'investments' => $this->polyline($series, 'investments'),
        ];
    }

    /**
     * @return array{empty: bool, range: string, labels: list<string>, registered: string, active: string}
     */
    public function growth(string $range): array
    {
        [$from, $days, $range] = $this->window($range);
        $registered = $this->dailyCounts(User::query()->where('role', UserRole::User), 'created_at', $from);
        $active = LedgerEntry::query()
            ->where('status', LedgerStatus::Completed)
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, COUNT(DISTINCT user_id) as total')
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'day')
            ->all();
        $series = $this->fill($from, $days, [
            'registered' => $registered,
            'active' => $active,
        ]);

        return [
            'empty' => $this->isEmpty($series, ['registered', 'active']),
            'range' => $range,
            'labels' => array_column($series, 'label'),
            'registered' => $this->polyline($series, 'registered'),
            'active' => $this->polyline($series, 'active'),
        ];
    }

    /**
     * @return array{empty: bool, rows: list<array{label: string, amount: string, width: int}>}
     */
    public function funds(): array
    {
        $rows = [
            ['label' => 'Disponible', 'amount' => Money::of(Wallet::query()->sum('available_balance'))],
            ['label' => 'Investi', 'amount' => Money::of(Wallet::query()->sum('invested_balance'))],
            ['label' => 'Revenus distribués', 'amount' => $this->ledgerSum(LedgerType::InvestmentReturn)],
            ['label' => 'Retraits versés', 'amount' => Money::of(Withdrawal::query()->where('status', ReviewStatus::Approved)->sum('net_amount'))],
        ];
        $max = '0.00';
        foreach ($rows as $row) {
            if (Money::cmp($row['amount'], $max) > 0) {
                $max = $row['amount'];
            }
        }
        $empty = Money::cmp($max, '0') <= 0;
        foreach ($rows as &$row) {
            $row['width'] = $empty ? 0 : (int) max(2, round(((float) $row['amount'] / (float) $max) * 100));
        }

        return ['empty' => $empty, 'rows' => $rows];
    }

    private function ledgerSum(LedgerType $type): string
    {
        return Money::of(LedgerEntry::query()->where('type', $type)->where('status', LedgerStatus::Completed)->sum('amount'));
    }

    /**
     * @return array{0: Carbon, 1: int, 2: string}
     */
    private function window(string $range): array
    {
        $range = in_array($range, ['7d', '30d', '90d', '1y'], true) ? $range : '30d';
        $days = match ($range) {
            '7d' => 7,
            '90d' => 90,
            '1y' => 365,
            default => 30,
        };

        return [now()->startOfDay()->subDays($days - 1), $days, $range];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return array<string, string>
     */
    private function dailyTotals($query, string $column, Carbon $from): array
    {
        return $query
            ->where($column, '>=', $from)
            ->selectRaw('DATE('.$column.') as day, SUM(amount) as total')
            ->groupByRaw('DATE('.$column.')')
            ->pluck('total', 'day')
            ->map(fn ($total) => Money::of($total))
            ->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return array<string, int>
     */
    private function dailyCounts($query, string $column, Carbon $from): array
    {
        return $query
            ->where($column, '>=', $from)
            ->selectRaw('DATE('.$column.') as day, COUNT(*) as total')
            ->groupByRaw('DATE('.$column.')')
            ->pluck('total', 'day')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * @param  array<string, array<string, mixed>>  $buckets
     * @return list<array<string, mixed>>
     */
    private function fill(Carbon $from, int $days, array $buckets): array
    {
        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $point = ['label' => Carbon::parse($day)->format('d/m'), 'day' => $day];
            foreach ($buckets as $key => $values) {
                $point[$key] = $values[$day] ?? (is_string(reset($values) ?: null) || $values === [] ? '0.00' : 0);
                if ($values !== [] && is_string(reset($values))) {
                    $point[$key] = $values[$day] ?? '0.00';
                } else {
                    $point[$key] = (int) ($values[$day] ?? 0);
                }
            }
            $series[] = $point;
        }

        return $series;
    }

    /**
     * @param  list<array<string, mixed>>  $series
     * @param  list<string>  $keys
     */
    private function isEmpty(array $series, array $keys): bool
    {
        foreach ($series as $point) {
            foreach ($keys as $key) {
                $value = $point[$key];
                if (is_int($value) && $value > 0) {
                    return false;
                }
                if (is_string($value) && Money::cmp($value, '0') > 0) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $series
     */
    private function polyline(array $series, string $key): string
    {
        $values = array_map(fn (array $point) => (float) $point[$key], $series);
        $max = max($values);
        $count = count($values);
        if ($count === 0 || $max <= 0) {
            return '';
        }
        $dots = [];
        foreach ($values as $index => $value) {
            $x = $count === 1 ? 160 : ($index / ($count - 1)) * 320;
            $y = 100 - (($value / $max) * 80);
            $dots[] = round($x, 2).','.round($y, 2);
        }

        return implode(' ', $dots);
    }
}
