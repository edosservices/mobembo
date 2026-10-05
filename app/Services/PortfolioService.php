<?php

namespace App\Services;

use App\Enums\InvestmentStatus;
use App\Enums\LedgerStatus;
use App\Enums\LedgerType;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Support\Money;
use App\Support\ReturnEstimator;

class PortfolioService
{
    public function summary(User $user): array
    {
        $wallet = $user->wallet()->firstOrFail();
        $active = $user->investments()->where('status', InvestmentStatus::Active)->get();
        $estimate = '0.00';

        foreach ($active as $investment) {
            $estimate = Money::add(
                $estimate,
                ReturnEstimator::daily($investment->amount, $investment->expected_return_percent, (int) $investment->duration_days),
            );
        }

        return [
            'available' => Money::of($wallet->available_balance),
            'locked' => Money::of($wallet->locked_balance),
            'invested' => Money::of($wallet->invested_balance),
            'returns_total' => $this->sum($user, LedgerType::InvestmentReturn),
            'returns_today' => $this->sum($user, LedgerType::InvestmentReturn, true),
            'estimate_today' => $estimate,
            'bonus' => $this->sum($user, LedgerType::Bonus),
            'commissions' => $this->sum($user, LedgerType::ReferralCommission),
            'active_count' => $active->count(),
        ];
    }

    private function sum(User $user, LedgerType $type, bool $today = false): string
    {
        $query = LedgerEntry::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->where('status', LedgerStatus::Completed);

        if ($today) {
            $query->whereDate('created_at', today());
        }

        $total = '0.00';
        $query->orderBy('id')->each(function (LedgerEntry $entry) use (&$total) {
            $total = Money::add($total, $entry->amount);
        });

        return $total;
    }
}
