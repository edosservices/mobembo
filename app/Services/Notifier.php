<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PlatformNotification;

class Notifier
{
    public function send(User $user, string $kind, string $title, string $body): void
    {
        $user->notify(new PlatformNotification($title, $body, $kind));
    }
}
