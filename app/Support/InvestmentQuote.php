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

    public static function for(mixed $capital, mixed $percent, int $days): self
    {
        $total = ReturnEstimator::total($capital, $percent);

        return new self(
            Money::of($capital),
            max(1, $days),
            ReturnEstimator::durationPercent($percent),
            ReturnEstimator::dailyPercent($percent, $days),
            ReturnEstimator::dailyAmount($capital, $percent, $days),
            $total,
            Money::add($capital, $total),
        );
    }

    public function totalPercentLabel(): string
    {
        return str_replace('.', ',', $this->totalPercent).' %';
    }
}
