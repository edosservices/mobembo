<?php

namespace App\Policies;

use App\Models\JobOffer;
use App\Models\User;

class JobOfferPolicy
{
    public function view(User $user, JobOffer $offer): bool
    {
        return $user->is_active && $user->id === $offer->user_id;
    }

    public function update(User $user, JobOffer $offer): bool
    {
        return $this->view($user, $offer);
    }

    public function delete(User $user, JobOffer $offer): bool
    {
        return $this->view($user, $offer);
    }
}
