<?php

namespace App\Models;

use App\Enums\InvestmentStatus;
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
}
