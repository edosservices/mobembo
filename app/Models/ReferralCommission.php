<?php

namespace App\Models;

use App\Enums\ReferralTrigger;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'referrer_id',
    'referred_user_id',
    'trigger',
    'rate_percent',
    'base_amount',
    'amount',
    'source_type',
    'source_id',
    'ledger_entry_id',
])]
class ReferralCommission extends Model
{
    protected function casts(): array
    {
        return [
            'trigger' => ReferralTrigger::class,
            'rate_percent' => 'decimal:4',
            'base_amount' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
