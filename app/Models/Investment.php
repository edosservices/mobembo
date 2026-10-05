<?php

namespace App\Models;

use App\Enums\InvestmentStatus;
use App\Support\InvestmentQuote;
use App\Support\ReturnEstimator;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uuid',
    'user_id',
    'project_id',
    'amount',
    'currency',
    'expected_return_percent',
    'duration_days',
    'returns_credited',
    'capital_returned',
    'invested_at',
    'starts_at',
    'ends_at',
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
            'returns_credited' => 'decimal:2',
            'capital_returned' => 'decimal:2',
            'status' => InvestmentStatus::class,
            'invested_at' => 'datetime',
            'starts_at' => 'date',
            'ends_at' => 'date',
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

    public function estimatedReturn(): string
    {
        return ReturnEstimator::total($this->amount, $this->expected_return_percent);
    }

    public function estimatedDailyReturn(): string
    {
        return ReturnEstimator::daily($this->amount, $this->expected_return_percent, (int) $this->duration_days);
    }

    public function quote(): InvestmentQuote
    {
        return InvestmentQuote::for($this->amount, $this->expected_return_percent, (int) $this->duration_days);
    }

    public function dailyReturnPercent(): string
    {
        return ReturnEstimator::dailyPercent($this->expected_return_percent, (int) $this->duration_days);
    }

    public function estimatedDailyAmount(): string
    {
        return ReturnEstimator::dailyAmount($this->amount, $this->expected_return_percent, (int) $this->duration_days);
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

    public function estimatedAccruedReturn(): string
    {
        return bcmul($this->estimatedDailyAmount(), (string) $this->elapsedDays(), 4);
    }
}
