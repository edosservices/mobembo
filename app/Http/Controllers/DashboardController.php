<?php

namespace App\Http\Controllers;

use App\Enums\InvestmentStatus;
use App\Services\BadgeService;
use App\Services\PortfolioAnalyticsService;
use App\Services\PortfolioService;
use App\Services\ReferralProgressService;
use App\Support\Money;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PortfolioService $portfolio, PortfolioAnalyticsService $analytics, ReferralProgressService $progress, BadgeService $badges)
    {
        $user = $request->user();
        $summary = $portfolio->summary($user);
        $name = trim((string) $user->name);
        $first = strtok($name, ' ') ?: $name;
        $range = $request->string('range')->toString();
        $team = $progress->snapshot($user);

        return view('dashboard', [
            'summary' => $summary,
            'firstName' => $first,
            'investUrl' => Money::cmp($summary['available'], '0') > 0
                ? route('projects.index')
                : route('deposits.create'),
            'investments' => $user->investments()
                ->with('project')
                ->where('status', InvestmentStatus::Active)
                ->latest('invested_at')
                ->get(),
            'activity' => $user->ledgerEntries()->latest()->limit(6)->get(),
            'chart' => $analytics->series($user, $range),
            'team' => $team,
            'badges' => $badges->evaluate($team),
        ]);
    }
}
