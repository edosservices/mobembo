<?php

namespace App\Models;

use App\Enums\KycStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'document_type',
    'path',
    'status',
    'review_note',
    'reviewed_by',
    'reviewed_at',
])]
class KycDocument extends Model
{
    protected function casts(): array
    {
        return [
            'status' => KycStatus::class,
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

    public function typeLabel(): string
    {
        return match ($this->document_type) {
            'id_card' => 'Carte d’identité',
            'passport' => 'Passeport',
            'proof_of_address' => 'Justificatif de domicile',
            default => $this->document_type,
        };
    }
}
