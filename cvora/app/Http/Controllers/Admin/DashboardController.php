<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\ContactMessage;
use App\Models\CoverLetter;
use App\Models\PaymentTransaction;
use App\Models\Resume;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'users' => User::query()->count(),
            'resumes' => Resume::query()->count(),
            'letters' => CoverLetter::query()->count(),
            'paid' => PaymentTransaction::query()->where('status', TransactionStatus::Paid)->count(),
            'aiCalls' => AiUsageLog::query()->where('status', 'success')->where('created_at', '>=', now()->startOfMonth())->count(),
            'messages' => ContactMessage::query()->latest()->limit(5)->get(),
        ]);
    }
}
