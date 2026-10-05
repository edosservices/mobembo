<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LedgerStatus;
use App\Enums\LedgerType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\PlatformSetting;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Services\ReferralProgressService;
use App\Support\Money;

class ReferralController extends Controller
{
    public function index(ReferralProgressService $progress)
    {
        $commissions = ReferralCommission::query()->with(['referrer', 'referred'])->latest()->paginate(30);
        $top = User::query()
            ->where('role', UserRole::User)
            ->withSum('commissionsEarned as commissions_total', 'amount')
            ->orderByDesc('commissions_total')
            ->limit(5)
            ->get()
            ->filter(fn (User $user) => (float) $user->commissions_total > 0);

        return view('admin.referrals.index', [
            'commissions' => $commissions,
            'sponsors' => User::query()->where('role', UserRole::User)->has('referrals')->count(),
            'members' => User::query()->where('role', UserRole::User)->whereNotNull('referred_by_id')->count(),
            'paid' => Money::of(ReferralCommission::query()->sum('amount')),
            'bonus' => Money::of(LedgerEntry::query()->where('type', LedgerType::Bonus)->where('status', LedgerStatus::Completed)->sum('amount')),
            'levels' => $progress->rules(),
            'benefits' => config('zelvora.level_benefits'),
            'rate' => PlatformSetting::current()->referral_rate_percent,
            'top' => $top,
        ]);
    }
}
