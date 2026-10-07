<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PortfolioService;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function read(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return back();
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Notifications marquées comme lues.');
    }

    public function count(Request $request)
    {
        return response()->json([
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function feed(Request $request, PortfolioService $portfolio)
    {
        $user = $request->user();
        $since = $this->since($request->query('since'));
        $movements = $since === null
            ? collect()
            : $user->notifications()
                ->where('created_at', '>=', $since)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(15)
                ->get()
                ->reverse()
                ->values();
        $latest = $user->notifications()->latest('created_at')->latest('id')->first();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'cursor' => $latest?->created_at?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
            'latest_id' => $latest?->id,
            'movements' => $movements->map(fn (DatabaseNotification $notification) => $this->movement($notification))->values(),
            'balances' => $movements->isNotEmpty() && $user->wallet()->exists()
                ? $this->balances($user, $portfolio)
                : null,
        ]);
    }

    private function since(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }

    /**
     * @return array{id: string, title: string, body: string, kind: string, url: ?string, created_at: ?string}
     */
    private function movement(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'title' => (string) ($data['title'] ?? 'Notification'),
            'body' => (string) ($data['body'] ?? ''),
            'kind' => (string) ($data['kind'] ?? 'message'),
            'url' => $this->safeUrl($data['url'] ?? null),
            'created_at' => $notification->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function safeUrl(mixed $url): ?string
    {
        if (! is_string($url) || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return null;
        }

        return $url;
    }

    /**
     * @return array<string, string>
     */
    private function balances(User $user, PortfolioService $portfolio): array
    {
        $summary = $portfolio->summary($user);

        return [
            'invested' => money($summary['invested']),
            'available' => money($summary['available']),
            'profit' => money($summary['profit_available']),
            'bonus' => money($summary['bonus']),
            'commission' => money($summary['commissions']),
            'total' => money($summary['portfolio_total']),
            'pending' => money($summary['withdrawals_pending']),
            'paid' => money($summary['withdrawals_paid']),
        ];
    }
}
