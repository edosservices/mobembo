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
    'fee',
    'net_amount',
    'currency',
    'method',
    'phone',
    'status',
    'processing_at',
    'reviewed_by',
    'reviewed_at',
    'rejection_reason',
    'idempotency_key',
])]
class Withdrawal extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => ReviewStatus::class,
            'processing_at' => 'datetime',
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

    public function reference(): string
    {
        return 'WD-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function maskedPhone(): string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone) ?? '';

        if (strlen($digits) < 4) {
            return '••••';
        }

        return '••••'.substr($digits, -4);
    }
}
