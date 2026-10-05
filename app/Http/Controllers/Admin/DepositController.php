<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\DepositService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DepositController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->toString();

        $deposits = Deposit::query()
            ->with('user')
            ->when(ReviewStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.deposits.index', compact('deposits', 'status'));
    }

    public function show(Deposit $deposit)
    {
        $deposit->load(['user', 'reviewer']);

        return view('admin.deposits.show', compact('deposit'));
    }

    public function approve(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $deposits->approve($deposit, $request->user());

        return back()->with('success', 'Dépôt approuvé et solde crédité.');
    }

    public function reject(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $deposits->reject($deposit, $request->user(), $data['reason']);

        return back()->with('success', 'Dépôt refusé. Aucun crédit n’a été passé.');
    }

    public function proof(Deposit $deposit)
    {
        abort_unless(str_starts_with($deposit->proof_path, 'deposits/'), 404);
        abort_unless(Storage::disk('local')->exists($deposit->proof_path), 404);

        return Storage::disk('local')->response($deposit->proof_path);
    }
}
