<?php

namespace App\Services;

use App\Enums\InvestmentStatus;
use App\Enums\LedgerType;
use App\Models\Investment;
use App\Models\InvestmentProfit;
use App\Models\User;
use App\Support\BusinessCalendar;
use App\Support\Money;
use App\Support\ReturnEstimator;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Crédite le profit quotidien dans le solde retirable.
 * Le jour calendaire de l'activation est toujours payé, y compris le samedi et le dimanche.
 * Les jours suivants suivent le calendrier ouvré : lundi à vendredi.
 * Un couple investissement + date ne peut être crédité qu'une fois.
 */
class DailyProfitService
{
    public function __construct(
        private WalletService $wallets,
        private Notifier $notifier,
        private AuditService $audit,
    ) {}

    public function dailyAmount(Investment $investment): string
    {
        return Money::of(ReturnEstimator::daily(
            $investment->amount,
            $investment->expected_return_percent,
            (int) $investment->duration_days,
        ));
    }

    /**
     * @return array{credited: int, matured: int, errors: int}
     */
    public function process(?CarbonInterface $today = null): array
    {
        $today = $this->day($today);
        $credited = 0;
        $matured = 0;
        $errors = 0;

        Investment::query()
            ->where('status', InvestmentStatus::Active)
            ->orderBy('id')
            ->chunkById(50, function ($investments) use ($today, &$credited, &$matured, &$errors) {
                foreach ($investments as $investment) {
                    try {
                        $credited += $this->creditDue($investment, $today);

                        if ($this->settleMatured($investment, $today)) {
                            $matured++;
                        }
                    } catch (Throwable $exception) {
                        $errors++;
                        Log::error('Profit quotidien interrompu', [
                            'investment_id' => $investment->id,
                            'message' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        return compact('credited', 'matured', 'errors');
    }

    public function creditDue(Investment $investment, ?CarbonInterface $today = null): int
    {
        $today = $this->day($today);
        $start = $investment->starts_at->copy()->startOfDay();
        $floor = $investment->profit_effective_from?->copy()->startOfDay();

        if ($floor && $floor->gt($start)) {
            $start = $floor;
        }

        $end = $investment->ends_at->copy()->startOfDay();
        $last = $today->lt($end) ? $today->copy() : $end->copy()->subDay();

        if ($last->lt($start)) {
            return 0;
        }

        $known = InvestmentProfit::query()
            ->where('investment_id', $investment->id)
            ->pluck('profit_date')
            ->map(fn ($date) => $date instanceof CarbonInterface ? $date->toDateString() : substr((string) $date, 0, 10))
            ->flip();

        $count = 0;
        $cursor = $start->copy();
        $first = $this->firstAccrualDate($investment);

        while ($cursor->lte($last)) {
            $key = $cursor->toDateString();
            $payable = $key === $first || BusinessCalendar::growsOn($cursor);

            if ($payable && ! isset($known[$key]) && $this->creditDay($investment, $cursor)) {
                $count++;
                $known[$key] = true;
            }

            $cursor->addDay();
        }

        return $count;
    }

    public function creditDay(Investment $investment, CarbonInterface $day): bool
    {
        $day = $this->day($day);

        try {
            return $this->storeDay($investment, $day);
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    private function storeDay(Investment $investment, Carbon $day): bool
    {
        return (bool) Finance::run(function () use ($investment, $day) {
            $investment = Investment::query()->whereKey($investment->id)->lockForUpdate()->firstOrFail();

            if ($investment->status !== InvestmentStatus::Active) {
                return false;
            }

            $date = $day->toDateString();

            if ($date < $investment->starts_at->toDateString() || $date >= $investment->ends_at->toDateString()) {
                return false;
            }

            if (! $this->isPayable($investment, $date)) {
                return false;
            }

            if (InvestmentProfit::query()->where('investment_id', $investment->id)->whereDate('profit_date', $date)->exists()) {
                return false;
            }

            $amount = $this->dailyAmount($investment);
            $entryId = null;

            if (Money::cmp($amount, '0') > 0) {
                $user = User::query()->findOrFail($investment->user_id);
                $entry = $this->wallets->credit($user, $amount, LedgerType::InvestmentReturn, [
                    'description' => 'Profit quotidien du '.$day->format('d/m/Y'),
                    'reference' => 'PROFIT-'.$investment->id.'-'.$date,
                    'related_type' => Investment::class,
                    'related_id' => $investment->id,
                    'idempotency_key' => 'daily-profit-'.$investment->id.'-'.$date,
                    'metadata' => [
                        'type' => 'daily_profit',
                        'profit_date' => $date,
                        'plan_id' => $investment->project_id,
                    ],
                ]);
                $entryId = $entry->id;
                $investment->forceFill([
                    'returns_credited' => Money::add($investment->returns_credited, $amount),
                ])->save();

                $this->notifier->send(
                    $user,
                    'daily_profit',
                    'Profit quotidien crédité',
                    Money::format($amount).' ont été ajoutés à votre solde retirable pour le '.$day->format('d/m/Y').'.',
                );
            }

            InvestmentProfit::query()->create([
                'user_id' => $investment->user_id,
                'investment_id' => $investment->id,
                'project_id' => $investment->project_id,
                'profit_date' => $date,
                'amount' => $amount,
                'type' => 'daily_profit',
                'status' => 'credited',
                'ledger_entry_id' => $entryId,
            ]);

            return Money::cmp($amount, '0') > 0;
        });
    }

    public function settleMatured(Investment $investment, ?CarbonInterface $today = null): bool
    {
        $today = $this->day($today);

        try {
            return $this->storeMaturity($investment, $today);
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    private function storeMaturity(Investment $investment, Carbon $today): bool
    {
        return (bool) Finance::run(function () use ($investment, $today) {
            $investment = Investment::query()->whereKey($investment->id)->lockForUpdate()->firstOrFail();

            if ($investment->status !== InvestmentStatus::Active || $today->toDateString() < $investment->ends_at->toDateString()) {
                return false;
            }

            $user = User::query()->findOrFail($investment->user_id);
            $remaining = Money::sub($investment->amount, $investment->capital_returned);

            if (Money::cmp($remaining, '0') > 0) {
                $this->wallets->returnCapital($user, $remaining, [
                    'description' => 'Capital restitué à échéance',
                    'reference' => 'CAP-'.$investment->id,
                    'related_type' => Investment::class,
                    'related_id' => $investment->id,
                    'idempotency_key' => 'capital-'.$investment->id,
                ]);
                $investment->capital_returned = $investment->amount;
            }

            $investment->status = InvestmentStatus::Completed;
            $investment->save();

            $this->audit->record(
                null,
                $user,
                'investment_matured',
                null,
                null,
                $remaining,
                'Échéance atteinte, capital rendu disponible.',
                ['investment_id' => $investment->id],
            );

            $this->notifier->send(
                $user,
                'investment_completed',
                'Investissement terminé',
                'La durée est atteinte. Le capital a été rendu disponible dans votre solde retirable.',
            );

            return true;
        });
    }

    private function isPayable(Investment $investment, string $date): bool
    {
        if ($date === $this->firstAccrualDate($investment)) {
            return true;
        }

        return BusinessCalendar::growsOn(Carbon::parse($date, (string) config('app.timezone')));
    }

    private function firstAccrualDate(Investment $investment): string
    {
        $start = $investment->starts_at->toDateString();
        $floor = $investment->profit_effective_from?->toDateString();

        return $floor !== null && $floor > $start ? $floor : $start;
    }

    private function day(?CarbonInterface $moment): Carbon
    {
        return Carbon::parse($moment ?? now())->timezone(config('app.timezone'))->startOfDay();
    }
}
