<?php

namespace App\Http\Controllers;

use App\Models\Investment;
use App\Models\Project;
use App\Services\InvestmentService;
use App\Support\Money;
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

        return view('investments.index', compact('investments'));
    }

    public function show(Request $request, Investment $investment)
    {
        abort_unless($investment->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
        $investment->load('project');

        return view('investments.show', compact('investment'));
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
            ->with('success', 'Investissement enregistré. Le rendement prévu reste une estimation tant qu’aucune distribution réelle n’est créditée.');
    }
}
