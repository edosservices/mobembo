<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\LedgerStatus;
use App\Enums\LedgerType;
use App\Enums\ProjectStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\Investment;
use App\Models\LedgerEntry;
use App\Models\Project;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\Money;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $series = collect(range(13, 0))->map(function (int $ago) {
            $date = today()->subDays($ago);
            $deposits = Deposit::query()->where('status', ReviewStatus::Approved)->whereDate('reviewed_at', $date)->sum('amount');
            $invested = Investment::query()->whereDate('invested_at', $date)->sum('amount');

            return [
                'label' => $date->locale('fr')->isoFormat('D MMM'),
                'deposits' => Money::of($deposits),
                'invested' => Money::of($invested),
            ];
        });

        $max = '1.00';
        foreach ($series as $point) {
            foreach (['deposits', 'invested'] as $key) {
                if (Money::cmp($point[$key], $max) > 0) {
                    $max = $point[$key];
                }
            }
        }

        $series = $series->map(function (array $point) use ($max) {
            $point['deposits_height'] = $this->height($point['deposits'], $max);
            $point['invested_height'] = $this->height($point['invested'], $max);

            return $point;
        });

        return view('admin.dashboard', [
            'stats' => [
                'users_total' => User::query()->where('role', UserRole::User)->count(),
                'users_active' => User::query()->where('role', UserRole::User)->where('status', AccountStatus::Active)->count(),
                'users_blocked' => User::query()->where('role', UserRole::User)->where('status', AccountStatus::Blocked)->count(),
                'deposits_pending' => Deposit::query()->where('status', ReviewStatus::Pending)->count(),
                'deposits_amount' => Money::of(Deposit::query()->where('status', ReviewStatus::Approved)->sum('amount')),
                'withdrawals_pending' => Withdrawal::query()->where('status', ReviewStatus::Pending)->count(),
                'withdrawals_amount' => Money::of(Withdrawal::query()->where('status', ReviewStatus::Approved)->sum('net_amount')),
                'invested' => Money::of(Investment::query()->whereIn('status', ['active', 'suspended'])->sum('amount')),
                'returns' => $this->sum(LedgerType::InvestmentReturn),
                'commissions' => $this->sum(LedgerType::ReferralCommission),
                'projects_active' => Project::query()->whereIn('status', ProjectStatus::investableCases())->count(),
                'volume' => Money::of(Deposit::query()->where('status', ReviewStatus::Approved)->sum('amount')),
            ],
            'series' => $series,
        ]);
    }

    private function sum(LedgerType $type): string
    {
        return Money::of(LedgerEntry::query()->where('type', $type)->where('status', LedgerStatus::Completed)->sum('amount'));
    }

    private function height(string $value, string $max): int
    {
        if (Money::cmp($max, '0') <= 0) {
            return 0;
        }

        $ratio = (float) bcdiv($value, $max, 4);

        return (int) max(0, min(100, round($ratio * 100)));
    }
}
