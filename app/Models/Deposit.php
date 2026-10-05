<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uuid',
    'user_id',
    'amount',
    'currency',
    'method',
    'reference',
    'reference_lock',
    'proof_path',
    'status',
    'reviewed_by',
    'reviewed_at',
    'rejection_reason',
    'idempotency_key',
    'ledger_entry_id',
])]
class Deposit extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => ReviewStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
