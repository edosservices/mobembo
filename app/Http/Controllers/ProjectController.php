<?php

namespace App\Http\Controllers;

use App\Enums\InvestmentStatus;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Services\InvestmentService;
use App\Support\Money;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::query()
            ->publicCatalog()
            ->orderBy('min_investment')
            ->orderBy('name')
            ->paginate(12);

        return view('projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        if ($project->status === ProjectStatus::Draft && ! auth()->user()?->isAdmin()) {
            abort(404);
        }

        $user = auth()->user();
        $mine = $user
            ?->investments()
            ->where('project_id', $project->id)
            ->latest('invested_at')
            ->get() ?? collect();
        $available = $user ? Money::of($user->wallet()->value('available_balance') ?? '0') : null;
        $canFundMinimum = $available !== null && Money::cmp($available, $project->min_investment) >= 0;
        $activeSlots = $mine->where('status', InvestmentStatus::Active)->count();
        $slotLimit = InvestmentService::MAX_ACTIVE_PER_PLAN;
        $preview = session('investment_preview');

        if (! is_array($preview) || (int) ($preview['project_id'] ?? 0) !== $project->id) {
            $preview = null;
        }

        return view('projects.show', compact('project', 'mine', 'available', 'canFundMinimum', 'preview', 'activeSlots', 'slotLimit'));
    }
}
