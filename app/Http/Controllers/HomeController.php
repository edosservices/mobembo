<?php

namespace App\Http\Controllers;

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

        return view('home', [
            'stats' => [
                'projects' => $projects->count(),
                'minimum' => $minimum !== null ? Money::of($minimum) : null,
                'maximum_plan' => $maximumPlan !== null ? Money::of($maximumPlan) : null,
            ],
        ]);
    }
}
