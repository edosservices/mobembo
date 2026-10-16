<?php

namespace App\Models\Concerns;

trait OwnedByUser
{
    public function resolveRouteBinding($value, $field = null): ?static
    {
        $userId = auth()->id();

        if ($userId === null) {
            return null;
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where($this->getTable().'.user_id', $userId)
            ->first();
    }
}
