<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Facades\DB;

class Notifier
{
    public function __construct(private PushService $push) {}

    public function send(User $user, string $kind, string $title, string $body, ?string $url = null): void
    {
        $user->notify(new PlatformNotification($title, $body, $kind, $url));

        DB::afterCommit(function () use ($user, $title, $body) {
            $this->push->send($user, $title, $body);
        });
    }
}
