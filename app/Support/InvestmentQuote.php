<?php

namespace App\Support;

/**
 * Estimation d'affichage. N'écrit jamais dans le ledger.
 */
class InvestmentQuote
{
    public function __construct(
        public string $capital,
        public int $days,
        public string $totalPercent,
        public string $dailyPercent,
        public string $dailyAmount,
        public string $totalReturn,
        public string $maturity,
    ) {}

    public static function for(mixed $capital, mixed $percent, int $days, ?int $profitDays = null): self
    {
        $total = PlanMath::totalGain($capital, $percent);
        $profitDays = $profitDays ?? max(1, $days);
        $daily = PlanMath::ordinaryDaily($total, $profitDays);

        return new self(
            Money::of($capital),
            max(1, $days),
            ReturnEstimator::durationPercent($percent),
            ReturnEstimator::dailyPercent($percent, max(1, $profitDays)),
            $daily,
            $total,
            PlanMath::economicTotal($capital, $total),
        );
    }

    public function totalPercentLabel(): string
    {
        return str_replace('.', ',', $this->totalPercent).' %';
    }
}
