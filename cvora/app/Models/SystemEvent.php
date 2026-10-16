<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['level', 'source', 'message', 'context'])]
class SystemEvent extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function record(string $level, string $source, string $message, array $context = []): void
    {
        $clean = preg_replace('/sk-[A-Za-z0-9_\-]{8,}/', '[clé]', $message) ?? $message;
        $clean = mb_substr($clean, 0, 500);

        static::query()->create([
            'level' => $level,
            'source' => $source,
            'message' => $clean,
            'context' => $context === [] ? null : $context,
            'created_at' => now(),
        ]);
    }
}
