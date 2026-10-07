<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\InvestmentProfit;
use App\Models\Project;
use App\Services\InvestmentService;
use App\Support\Money;
use App\Support\ReturnEstimator;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function index(Request $request)
    {
        $investments = $request->user()
            ->investments()
            ->with('project')
            ->latest('invested_at')
            ->paginate(15);

        $profits = InvestmentProfit::query()
            ->where('user_id', $request->user()->id)
            ->with('project:id,name')
            ->latest('profit_date')
            ->limit(20)
            ->get();

        return view('investments.index', compact('investments', 'profits'));
    }

    public function show(Request $request, Investment $investment)
    {
        abort_unless($investment->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
        $investment->load(['project', 'profits' => fn ($query) => $query->latest('profit_date')->limit(40)]);

        return view('investments.show', compact('investment'));
    }

    public function preview(Request $request, Project $project, InvestmentService $investments)
    {
        $request->merge([
            'amount' => Money::normalizeInput($request->input('amount')),
        ]);

        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        if (Money::cmp($data['amount'], $project->min_investment) < 0) {
            return back()
                ->withInput()
                ->withErrors(['amount' => 'L’investissement minimum est de '.Money::format($project->min_investment).'.']);
        }

        [$starts, $ends] = $investments->term($project);

        return redirect()
            ->route('projects.show', $project)
            ->withFragment('investir')
            ->with('investment_preview', [
                'project_id' => $project->id,
                'amount' => Money::of($data['amount']),
                'daily_percent' => ReturnEstimator::dailyPercent($project->expected_return_percent, (int) $project->duration_days),
                'daily_amount' => ReturnEstimator::dailyAmount($data['amount'], $project->expected_return_percent, (int) $project->duration_days),
                'total' => ReturnEstimator::total($data['amount'], $project->expected_return_percent),
                'duration_days' => (int) $project->duration_days,
                'starts_at' => $starts->toDateString(),
                'ends_at' => $ends->toDateString(),
                'idempotency_key' => $data['idempotency_key'],
                'fee' => '0.00',
            ]);
    }

    public function store(Request $request, Project $project, InvestmentService $investments)
    {
        $request->merge([
            'amount' => Money::normalizeInput($request->input('amount')),
        ]);

        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        $investment = $investments->invest(
            $request->user(),
            $project,
            $data['amount'],
            $data['idempotency_key'],
        );

        return redirect()
            ->route('investments.show', $investment)
            ->with('success', 'Votre investissement est confirmé.');
    }
}
