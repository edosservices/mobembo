<?php

namespace App\Http\Controllers;

use App\Services\ReferralProgressService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __invoke(Request $request, ReferralProgressService $progress)
    {
        $user = $request->user();

        return view('referral.index', [
            'team' => $progress->snapshot($user),
            'members' => $progress->members($user),
            'commissions' => $user->commissionsEarned()->with('referred')->latest()->limit(20)->get(),
        ]);
    }
}
