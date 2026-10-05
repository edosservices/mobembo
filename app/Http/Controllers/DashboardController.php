<?php

namespace App\Http\Controllers;

use App\Enums\InvestmentStatus;
use App\Services\PortfolioService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PortfolioService $portfolio)
    {
        $user = $request->user();

        return view('dashboard', [
            'summary' => $portfolio->summary($user),
            'investments' => $user->investments()
                ->with('project')
                ->where('status', InvestmentStatus::Active)
                ->latest('invested_at')
                ->take(4)
                ->get(),
        ]);
    }
}
