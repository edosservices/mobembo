<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvestmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Services\InvestmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvestmentController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();

        $investments = Investment::query()
            ->with(['user', 'project'])
            ->when(InvestmentStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->latest('invested_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.investments.index', compact('investments', 'status'));
    }

    public function update(Request $request, Investment $investment, InvestmentService $investments)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended', 'cancelled'])],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $investments->setStatus($investment, InvestmentStatus::from($data['status']), $request->user(), $data['reason']);

        return back()->with('success', 'Investissement mis à jour.');
    }
}
