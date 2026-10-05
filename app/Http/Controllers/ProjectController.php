<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::query()
            ->whereIn('status', [ProjectStatus::Active, ProjectStatus::Funded, ProjectStatus::Closed])
            ->latest()
            ->paginate(12);

        return view('projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        if ($project->status === ProjectStatus::Draft && ! auth()->user()?->isAdmin()) {
            abort(404);
        }

        $mine = auth()->user()
            ?->investments()
            ->where('project_id', $project->id)
            ->latest('invested_at')
            ->get();

        return view('projects.show', compact('project', 'mine'));
    }
}
