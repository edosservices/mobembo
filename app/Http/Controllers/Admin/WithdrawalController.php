<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();

        $withdrawals = Withdrawal::query()
            ->with('user')
            ->when(ReviewStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.withdrawals.index', compact('withdrawals', 'status'));
    }

    public function show(Withdrawal $withdrawal)
    {
        $withdrawal->load(['user', 'reviewer']);

        return view('admin.withdrawals.show', compact('withdrawal'));
    }

    public function approve(Request $request, Withdrawal $withdrawal, WithdrawalService $withdrawals)
    {
        $withdrawals->approve($withdrawal, $request->user());

        return back()->with('success', 'Retrait approuvé. Le net est à verser sur le numéro indiqué.');
    }

    public function reject(Request $request, Withdrawal $withdrawal, WithdrawalService $withdrawals)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $withdrawals->reject($withdrawal, $request->user(), $data['reason']);

        return back()->with('success', 'Retrait refusé et montant restitué au solde disponible.');
    }
}
