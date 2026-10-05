<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DistributionFrequency;
use App\Enums\InvestmentStatus;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\AuditService;
use App\Services\DistributionService;
use App\Services\InvestmentService;
use App\Support\Money;
use App\Support\ReturnEstimator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::query()->latest()->paginate(15);

        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        return view('admin.projects.form', ['project' => new Project]);
    }

    public function store(Request $request, AuditService $audit)
    {
        $project = new Project;
        $this->fill($project, $request);
        $project->save();
        $audit->record($request->user(), null, 'project_created', null, null, null, $project->name, ['project_id' => $project->id]);

        return redirect()->route('admin.projects.show', $project)->with('success', 'Projet créé.');
    }

    public function show(Project $project)
    {
        $investments = $project->investments()->with('user')->latest('invested_at')->paginate(20);
        $active = $project->investments()->where('status', InvestmentStatus::Active)->get();
        $estimatedOutstanding = '0.00';

        foreach ($active as $investment) {
            $estimated = ReturnEstimator::total($investment->amount, $investment->expected_return_percent);
            $remaining = Money::sub($estimated, $investment->returns_credited);
            if (Money::cmp($remaining, '0') > 0) {
                $estimatedOutstanding = Money::add($estimatedOutstanding, $remaining);
            }
        }

        return view('admin.projects.show', [
            'project' => $project,
            'investments' => $investments,
            'estimatedOutstanding' => $estimatedOutstanding,
            'distributions' => $project->distributions()->latest()->limit(10)->get(),
        ]);
    }

    public function edit(Project $project)
    {
        return view('admin.projects.form', compact('project'));
    }

    public function update(Request $request, Project $project, AuditService $audit)
    {
        $this->fill($project, $request);
        $project->save();
        $audit->record($request->user(), null, 'project_updated', null, null, null, $project->name, ['project_id' => $project->id]);

        return redirect()->route('admin.projects.show', $project)->with('success', 'Projet mis à jour.');
    }

    public function updateReturn(Request $request, Project $project, AuditService $audit)
    {
        $request->merge([
            'expected_return_percent' => str_replace(',', '.', (string) $request->input('expected_return_percent')),
        ]);

        $data = $request->validate([
            'expected_return_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $previous = (string) $project->expected_return_percent;
        $project->forceFill([
            'expected_return_percent' => $data['expected_return_percent'],
            'is_demo' => false,
        ])->save();

        $audit->record(
            $request->user(),
            null,
            'project_return_updated',
            $previous,
            $project->expected_return_percent,
            null,
            'Modification du rendement prévu de '.$project->name.'. Les investissements déjà ouverts conservent leur taux.',
            ['project_id' => $project->id],
        );

        return back()->with('success', 'Le rendement du projet a été mis à jour.');
    }

    public function distribute(Request $request, Project $project, DistributionService $distributions)
    {
        $request->merge(['total_amount' => Money::normalizeInput($request->input('total_amount'))]);
        $data = $request->validate([
            'total_amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $distributions->distribute($project, $data['total_amount'], $data['reason'], $request->user());

        return back()->with('success', 'La distribution a été enregistrée.');
    }

    public function suspend(Request $request, Project $project, AuditService $audit)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $project->forceFill(['status' => ProjectStatus::Suspended])->save();
        $audit->record(
            $request->user(),
            null,
            'project_suspended',
            null,
            null,
            null,
            $data['reason'],
            ['project_id' => $project->id],
        );

        return back()->with('success', 'Le projet a été suspendu. Les soldes n’ont pas été modifiés.');
    }

    public function close(Request $request, Project $project, InvestmentService $investments)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $investments->closeProject($project, $request->user(), $data['reason']);

        return back()->with('success', 'Projet clôturé. Le capital restant a été restitué sur les soldes disponibles.');
    }

    public function destroy(Request $request, Project $project, AuditService $audit)
    {
        if ($project->investments()->exists()) {
            $project->forceFill(['status' => ProjectStatus::Suspended])->save();
            $audit->record($request->user(), null, 'project_suspended', null, null, null, 'Projet suspendu : des investissements existent.', [
                'project_id' => $project->id,
            ]);

            return back()->with('success', 'Ce projet a déjà des investissements. Il a été suspendu, pas supprimé.');
        }

        $audit->record($request->user(), null, 'project_deleted', null, null, null, $project->name, [
            'project_id' => $project->id,
            'slug' => $project->slug,
        ]);
        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Projet supprimé.');
    }

    private function fill(Project $project, Request $request): void
    {
        $request->merge([
            'target_amount' => Money::normalizeInput($request->input('target_amount')),
            'min_investment' => Money::normalizeInput($request->input('min_investment')),
            'expected_return_percent' => str_replace(',', '.', (string) $request->input('expected_return_percent')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160'],
            'currency' => ['required', 'string', 'max:8'],
            'description' => ['required', 'string', 'max:5000'],
            'location' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:80'],
            'target_amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'min_investment' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'expected_return_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'distribution_frequency' => ['required', Rule::enum(DistributionFrequency::class)],
            'economic_terms' => ['required', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'next_distribution_on' => ['nullable', 'date'],
            'is_demo' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if (Money::cmp($data['min_investment'], $data['target_amount']) > 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'min_investment' => 'Le minimum ne peut pas dépasser l’objectif.',
            ]);
        }

        $slug = $this->slugFor($project, $data['name'], $data['slug'] ?? null);
        $image = $project->image_path;

        if ($request->hasFile('image')) {
            $image = $request->file('image')->store('projects', 'public');
        }

        $project->fill([
            'name' => $data['name'],
            'slug' => $slug,
            'currency' => strtoupper($data['currency']),
            'image_path' => $image,
            'description' => $data['description'],
            'location' => $data['location'],
            'category' => $data['category'],
            'target_amount' => $data['target_amount'],
            'min_investment' => $data['min_investment'],
            'duration_days' => $data['duration_days'],
            'expected_return_percent' => $data['expected_return_percent'],
            'distribution_frequency' => $data['distribution_frequency'],
            'economic_terms' => $data['economic_terms'],
            'status' => $data['status'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'next_distribution_on' => $data['next_distribution_on'] ?? null,
            'is_demo' => false,
        ]);
    }

    private function slugFor(Project $project, string $name, ?string $requested): string
    {
        $candidate = filled($requested) ? Str::slug($requested) : ($project->slug ?: $this->uniqueSlug($name));

        if ($candidate === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'slug' => 'Indiquez une adresse publique lisible, par exemple residence-gombe.',
            ]);
        }

        $taken = Project::query()
            ->where('slug', $candidate)
            ->when($project->exists, fn ($query) => $query->whereKeyNot($project->id))
            ->exists();

        if ($taken) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'slug' => 'Cette adresse publique est déjà utilisée.',
            ]);
        }

        return $candidate;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'projet';
        $slug = $base;
        $index = 2;

        while (Project::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$index;
            $index++;
        }

        return $slug;
    }
}
