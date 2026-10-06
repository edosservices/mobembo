<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Support\Money;

class HomeController extends Controller
{
    public function index()
    {
        $projects = Project::query()
            ->publicCatalog()
            ->orderBy('min_investment')
            ->orderBy('name')
            ->get();

        $investable = $projects->filter(fn (Project $project) => $project->status->acceptsInvestment());
        $minimum = $investable->min('min_investment');
        $maximumPlan = $projects->max('min_investment');

        $featured = Project::query()
            ->where('is_demo', false)
            ->whereIn('status', array_map(fn (ProjectStatus $status) => $status->value, ProjectStatus::investableCases()))
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'active' THEN 1 ELSE 2 END")
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get()
            ->filter(fn (Project $project) => $project->isInvestable())
            ->take(3)
            ->values();

        return view('home', [
            'featured' => $featured,
            'stats' => [
                'projects' => $projects->count(),
                'minimum' => $minimum !== null ? Money::of($minimum) : null,
                'maximum_plan' => $maximumPlan !== null ? Money::of($maximumPlan) : null,
            ],
        ]);
    }
}
