<?php

namespace App\Policies;

use App\Models\ResumeImport;
use App\Models\User;

class ResumeImportPolicy
{
    public function view(User $user, ResumeImport $import): bool
    {
        return $user->is_active && $user->id === $import->user_id;
    }

    public function update(User $user, ResumeImport $import): bool
    {
        return $this->view($user, $import);
    }
}
