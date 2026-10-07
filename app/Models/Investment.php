<?php

namespace App\Models;

use App\Enums\InvestmentStatus;
use App\Support\BusinessCalendar;
use App\Support\InvestmentQuote;
use App\Support\Money;
use App\Support\PlanMath;
use App\Support\ReturnEstimator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'uuid',
    'user_id',
    'project_id',
    'amount',
    'currency',
    'expected_return_percent',
    'duration_days',
    'planned_return',
    'profit_days',
    'daily_return',
    'returns_credited',
    'capital_returned',
    'invested_at',
    'starts_at',
    'ends_at',
    'profit_effective_from',
    'status',
    'idempotency_key',
])]
class Investment extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expected_return_percent' => 'decimal:4',
            'planned_return' => 'decimal:2',
            'daily_return' => 'decimal:2',
            'returns_credited' => 'decimal:2',
            'capital_returned' => 'decimal:2',
            'status' => InvestmentStatus::class,
            'invested_at' => 'datetime',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'profit_effective_from' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function profits(): HasMany
    {
        return $this->hasMany(InvestmentProfit::class);
    }

    public function plannedReturn(): string
    {
        if ($this->planned_return !== null) {
            return Money::of($this->planned_return);
        }

        return PlanMath::totalGain($this->amount, $this->expected_return_percent);
    }

    public function profitDayCount(): int
    {
        if ($this->profit_days !== null) {
            return (int) $this->profit_days;
        }

        if ($this->starts_at === null || $this->ends_at === null) {
            return max(0, (int) $this->duration_days);
        }

        return BusinessCalendar::scheduledProfitDays($this->starts_at, $this->ends_at);
    }

    public function ordinaryDailyReturn(): string
    {
        if ($this->daily_return !== null) {
            return Money::of($this->daily_return);
        }

        return PlanMath::ordinaryDaily($this->plannedReturn(), $this->profitDayCount());
    }

    public function scheduledProfits(): string
    {
        return Money::of($this->profits()->sum('amount') ?: '0');
    }

    public function remainingProfit(): string
    {
        $remaining = Money::sub($this->plannedReturn(), $this->scheduledProfits());

        return Money::cmp($remaining, '0') < 0 ? '0.00' : $remaining;
    }

    public function capitalAtMaturity(): string
    {
        return Money::of($this->amount);
    }

    public function economicTotal(): string
    {
        return PlanMath::economicTotal($this->amount, $this->plannedReturn());
    }

    public function estimatedReturn(): string
    {
        return $this->plannedReturn();
    }

    public function estimatedDailyReturn(): string
    {
        return $this->ordinaryDailyReturn();
    }

    public function quote(): InvestmentQuote
    {
        return InvestmentQuote::for(
            $this->amount,
            $this->expected_return_percent,
            (int) $this->duration_days,
            $this->profitDayCount(),
        );
    }

    public function dailyReturnPercent(): string
    {
        return ReturnEstimator::dailyPercent($this->expected_return_percent, (int) $this->duration_days);
    }

    public function estimatedDailyAmount(): string
    {
        return $this->ordinaryDailyReturn();
    }

    public function elapsedDays(): int
    {
        if ($this->starts_at === null) {
            return 0;
        }

        $start = $this->starts_at->copy()->startOfDay();
        $end = ($this->ends_at ?? $start->copy()->addDays(max(1, (int) $this->duration_days)))->copy()->startOfDay();
        $today = now()->startOfDay();

        if ($today->lt($start)) {
            return 0;
        }

        $elapsed = (int) $start->diffInDays($today, false);
        $span = max(0, (int) $start->diffInDays($end, false));

        return min($elapsed, $span);
    }

    public function remainingDays(): int
    {
        if ($this->ends_at === null) {
            return max(0, (int) $this->duration_days - $this->elapsedDays());
        }

        $today = now()->startOfDay();
        $end = $this->ends_at->copy()->startOfDay();

        if ($today->gte($end)) {
            return 0;
        }

        return (int) $today->diffInDays($end, false);
    }

    public function progressPercent(): string
    {
        $span = max(1, (int) $this->duration_days);
        $raw = bcdiv(bcmul((string) $this->elapsedDays(), '100', 4), (string) $span, 4);

        return bcadd($raw, '0', 2);
    }

    public function accrualDays(): int
    {
        if ($this->starts_at === null) {
            return 0;
        }

        $start = $this->starts_at->copy()->startOfDay();
        $end = ($this->ends_at ?? $start->copy()->addDays(max(1, (int) $this->duration_days)))->copy()->startOfDay();

        return BusinessCalendar::accrualDays($start, $end);
    }

    public function estimatedAccruedReturn(): string
    {
        $raw = bcmul($this->ordinaryDailyReturn(), (string) $this->accrualDays(), 4);
        $estimate = Money::of($raw);
        $planned = $this->plannedReturn();

        return Money::cmp($estimate, $planned) > 0 ? $planned : $estimate;
    }
}
