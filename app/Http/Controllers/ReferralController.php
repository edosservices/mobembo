<?php

namespace App\Http\Controllers;

use App\Services\ReferralProgressService;
use App\Support\Money;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __invoke(Request $request, ReferralProgressService $progress)
    {
        $user = $request->user();
        $earned = Money::of($user->commissionsEarned()->sum('amount'));
        $stillThere = Money::of($user->wallet->available_balance);
        $available = Money::cmp($stillThere, $earned) >= 0 ? $earned : $stillThere;

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
