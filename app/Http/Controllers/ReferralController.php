<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\ReviewStatus;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $filleuls = $user->referrals()->latest()->paginate(15);
        $activeCount = $user->referrals()
            ->where('status', AccountStatus::Active)
            ->whereHas('deposits', fn ($query) => $query->where('status', ReviewStatus::Approved))
            ->count();

        return view('referral.index', [
            'filleuls' => $filleuls,
            'activeCount' => $activeCount,
            'commissions' => $user->commissionsEarned()->with('referred')->latest()->limit(20)->get(),
            'settings' => PlatformSetting::current(),
            'total' => $user->commissionsEarned()->sum('amount'),
        ]);
    }
}
