<?php

namespace App\Services;

use App\Enums\InvestmentStatus;
use App\Enums\LedgerStatus;
use App\Enums\LedgerType;
use App\Enums\ReviewStatus;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\Money;
use App\Support\ReturnEstimator;
use Illuminate\Support\Collection;

class PortfolioService
{
    public function __construct(private AvailableComposition $composition) {}

    public function summary(User $user, ?Collection $active = null): array
    {
        $wallet = $user->wallet()->firstOrFail();
        $active ??= $user->investments()->where('status', InvestmentStatus::Active)->get();
        $estimate = '0.00';

        foreach ($active as $investment) {
            $estimate = Money::add(
                $estimate,
                ReturnEstimator::daily($investment->amount, $investment->expected_return_percent, (int) $investment->duration_days),
            );
        }

        $ledger = $this->ledgerTotals($user);
        $remaining = $this->composition->remaining($user);
        $withdrawals = $this->withdrawalTotals($user);
        $available = Money::of($wallet->available_balance);
        $locked = Money::of($wallet->locked_balance);
        $invested = Money::of($wallet->invested_balance);

        return [
            'available' => $available,
            'locked' => $locked,
            'invested' => $invested,
            'portfolio_total' => Money::add(Money::add($available, $locked), $invested),
            'returns_total' => $ledger['returns_total'],
            'returns_today' => $ledger['returns_today'],
            'profit_available' => $remaining['profit'],
            'estimate_today' => $estimate,
            'bonus' => $remaining['bonus'],
            'commissions' => $remaining['commission'],
            'withdrawals_pending' => $withdrawals['pending'],
            'withdrawals_paid' => $withdrawals['paid'],
            'active_count' => $active->count(),
        ];
    }

    /**
     * @return array{returns_total: string, returns_today: string}
     */
    private function ledgerTotals(User $user): array
    {
        $row = LedgerEntry::query()
            ->where('user_id', $user->id)
            ->where('status', LedgerStatus::Completed)
            ->where('type', LedgerType::InvestmentReturn)
            ->selectRaw(
                'COALESCE(SUM(amount), 0) as returns_total,
                 COALESCE(SUM(CASE WHEN date(created_at) = ? THEN amount ELSE 0 END), 0) as returns_today',
                [today()->toDateString()],
            )
            ->first();

        return [
            'returns_total' => Money::of($row->returns_total ?? 0),
            'returns_today' => Money::of($row->returns_today ?? 0),
        ];
    }

    /**
     * @return array{pending: string, paid: string}
     */
    private function withdrawalTotals(User $user): array
    {
        $row = Withdrawal::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [ReviewStatus::Pending, ReviewStatus::Processing, ReviewStatus::Approved])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status IN (?, ?) THEN amount ELSE 0 END), 0) as pending,
                 COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) as paid',
                [ReviewStatus::Pending->value, ReviewStatus::Processing->value, ReviewStatus::Approved->value],
            )
            ->first();

        return [
            'pending' => Money::of($row->pending ?? 0),
            'paid' => Money::of($row->paid ?? 0),
        ];
    }
}
