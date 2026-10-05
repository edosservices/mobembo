<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Money;

class AuditService
{
    public function record(
        ?User $admin,
        ?User $subject,
        string $action,
        mixed $oldAmount,
        mixed $newAmount,
        mixed $delta,
        ?string $reason,
        array $metadata = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'admin_id' => $admin?->id,
            'user_id' => $subject?->id,
            'action' => $action,
            'old_amount' => $oldAmount === null ? null : Money::of($oldAmount),
            'new_amount' => $newAmount === null ? null : Money::of($newAmount),
            'delta_amount' => $delta === null ? null : Money::of($delta),
            'reason' => $reason,
            'metadata' => $metadata ?: null,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
