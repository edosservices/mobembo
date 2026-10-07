<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class PushService
{
    public function send(User $user, string $title, string $body): void
    {
        $public = (string) config('services.webpush.public_key');
        $private = (string) config('services.webpush.private_key');

        if ($public === '' || $private === '' || ! class_exists(WebPush::class)) {
            return;
        }

        $subscriptions = PushSubscription::query()->where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => (string) config('services.webpush.subject'),
                    'publicKey' => $public,
                    'privateKey' => $private,
                ],
            ]);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'url' => url('/notifications'),
            ], JSON_UNESCAPED_UNICODE);

            foreach ($subscriptions as $subscription) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding,
                    ]),
                    $payload,
                );
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::query()->where('endpoint', $report->getEndpoint())->delete();
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Notification push non envoyée', ['message' => $exception->getMessage()]);
        }
    }
}
