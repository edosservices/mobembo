<?php

namespace App\Policies;

use App\Models\JobApplication;
use App\Models\User;

class JobApplicationPolicy
{
    public function view(User $user, JobApplication $application): bool
    {
        return $user->is_active && $user->id === $application->user_id;
    }

    public function update(User $user, JobApplication $application): bool
    {
        return $this->view($user, $application);
    }

    public function delete(User $user, JobApplication $application): bool
    {
        return $this->view($user, $application);
    }
}
