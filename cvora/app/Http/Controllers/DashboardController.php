<?php

namespace App\Http\Controllers;

use App\Services\Accounts\AccountProvisioner;
use App\Services\Billing\QuotaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AccountProvisioner $accounts, QuotaService $quotas): View
    {
        $user = $request->user();
        $accounts->provision($user);
        $user->load('profile');

        $resumes = $user->resumes()->with('template')->latest('updated_at')->limit(4)->get();
        $letters = $user->coverLetters()->latest('updated_at')->limit(4)->get();

        return view('dashboard', [
            'resumeCount' => $user->resumes()->count(),
            'letterCount' => $user->coverLetters()->count(),
            'resumes' => $resumes,
            'letters' => $letters,
            'planName' => $quotas->planName($user),
            'credits' => $quotas->remainingCredits($user),
            'progress' => $user->progress(),
        ]);
    }
}
