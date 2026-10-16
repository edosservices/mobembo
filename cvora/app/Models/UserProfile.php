<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'headline',
    'summary',
    'phone',
    'city',
    'country',
    'address',
    'linkedin',
    'website',
    'job_target',
    'preferences',
])]
class UserProfile extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferences' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function preferences(): array
    {
        return is_array($this->preferences) ? $this->preferences : [];
    }
}
