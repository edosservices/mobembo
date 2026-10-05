<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Notifier;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function create()
    {
        return view('admin.notifications.create');
    }

    public function store(Request $request, Notifier $notifier, AuditService $audit)
    {
        $data = $request->validate([
            'audience' => ['required', 'in:all,user'],
            'phone' => ['nullable', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:1000'],
        ]);

        if ($data['audience'] === 'user') {
            $user = User::query()->where('phone', $data['phone'])->orWhere('phone', \App\Support\PhoneNumber::normalize($data['phone']))->first();
            abort_unless($user, 422, 'Utilisateur introuvable.');
            $notifier->send($user, 'admin_message', $data['title'], $data['body']);
            $count = 1;
        } else {
            $count = 0;
            User::query()->where('role', UserRole::User)->orderBy('id')->each(function (User $user) use ($notifier, $data, &$count) {
                $notifier->send($user, 'admin_message', $data['title'], $data['body']);
                $count++;
            });
        }

        $audit->record($request->user(), null, 'notification_sent', null, null, null, $data['title'], [
            'audience' => $data['audience'],
            'count' => $count,
        ]);

        return back()->with('success', $count.' notification(s) envoyée(s).');
    }
}
