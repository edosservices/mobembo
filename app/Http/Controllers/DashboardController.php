<?php

namespace App\Http\Controllers;

use App\Enums\InvestmentStatus;
use App\Services\PortfolioService;
use App\Support\Money;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PortfolioService $portfolio)
    {
        $user = $request->user();
        $summary = $portfolio->summary($user);
        $name = trim((string) $user->name);
        $first = strtok($name, ' ') ?: $name;

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
        ]);
    }
}
