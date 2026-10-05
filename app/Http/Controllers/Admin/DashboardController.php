<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Services\AdminOverviewService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AdminOverviewService $overview)
    {
        $range = $request->string('range')->toString();
        $name = trim((string) $request->user()->name);
        $first = strtok($name, ' ') ?: $name;

        return view('admin.dashboard', [
            'firstName' => $first,
            'stats' => $overview->stats(),
            'attention' => $overview->attention(),
            'activity' => $overview->activity($range),
            'growth' => $overview->growth($range),
            'funds' => $overview->funds(),
            'recent' => LedgerEntry::query()->with('user')->latest()->limit(8)->get(),
        ]);
    }
}
