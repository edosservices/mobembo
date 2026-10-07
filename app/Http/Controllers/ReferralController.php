<?php

namespace App\Http\Controllers;

use App\Services\AvailableComposition;
use App\Services\ReferralProgressService;
use App\Support\Money;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __invoke(Request $request, ReferralProgressService $progress, AvailableComposition $composition)
    {
        $user = $request->user();
        $earned = Money::of($user->commissionsEarned()->sum('amount'));
        $available = $composition->remaining($user)['commission'];

        if (Money::cmp($available, $earned) > 0) {
            $available = $earned;
        }

        return view('referral.index', [
            'team' => $progress->snapshot($user),
            'members' => $progress->members($user),
            'commissions' => $user->commissionsEarned()->with('referred')->latest()->limit(30)->get(),
            'commissionEarned' => $earned,
            'commissionAvailable' => $available,
            'commissionWithdrawn' => Money::sub($earned, $available),
        ]);
    }
}
