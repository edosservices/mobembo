<?php

namespace App\Models;

use App\Enums\LedgerStatus;
use App\Enums\LedgerType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uuid',
    'user_id',
    'wallet_id',
    'type',
    'amount',
    'currency',
    'status',
    'reference',
    'description',
    'balance_after',
    'related_type',
    'related_id',
    'idempotency_key',
    'metadata',
    'created_by',
])]
class LedgerEntry extends Model
{
    protected function casts(): array
    {
        return [
            'type' => LedgerType::class,
            'status' => LedgerStatus::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
