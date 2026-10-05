<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Presenters\ApiPresenter;
use App\Models\Project;
use App\Services\DepositService;
use App\Services\InvestmentService;
use App\Services\PortfolioService;
use App\Services\WithdrawalService;
use App\Support\Money;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlatformController extends Controller
{
    public function dashboard(Request $request, PortfolioService $portfolio)
    {
        $user = $request->user();

        return response()->json([
            'summary' => $portfolio->summary($user),
            'summary_notes' => [
                'returns_today' => 'Revenus réellement crédités aujourd’hui.',
                'estimate_today' => 'Estimation non garantie, non créditée.',
            ],
            'referral' => [
                'code' => $user->referral_code,
                'url' => url('/register?ref='.$user->referral_code),
            ],
        ]);
    }

    public function projects()
    {
        $projects = Project::query()
            ->whereIn('status', [ProjectStatus::Active, ProjectStatus::Funded, ProjectStatus::Closed])
            ->latest()
            ->paginate(20);

        return $projects->through(fn (Project $project) => ApiPresenter::project($project));
    }

    public function invest(Request $request, Project $project, InvestmentService $investments)
    {
        $request->merge(['amount' => Money::normalizeInput($request->input('amount'))]);
        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        $investment = $investments->invest($request->user(), $project, $data['amount'], $data['idempotency_key']);

        return response()->json(['investment' => ApiPresenter::investment($investment)], 201);
    }

    public function investments(Request $request)
    {
        $investments = $request->user()->investments()->with('project')->latest('invested_at')->paginate(20);

        return $investments->through(fn ($investment) => ApiPresenter::investment($investment));
    }

    public function deposit(Request $request, DepositService $deposits)
    {
        $request->merge([
            'amount' => Money::normalizeInput($request->input('amount')),
            'reference' => strtoupper(preg_replace('/\s+/', '', (string) $request->input('reference')) ?? ''),
        ]);

        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['required', 'regex:/^[A-Z0-9\-]{4,64}$/'],
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        $path = $request->file('proof')->store('deposits/'.$request->user()->id, 'local');

        try {
            $deposit = $deposits->submit(
                $request->user(),
                $data['amount'],
                PaymentMethod::from($data['method']),
                $data['reference'],
                $path,
                $data['idempotency_key'],
            );
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($path);
            throw $exception;
        }

        return response()->json([
            'deposit' => [
                'id' => $deposit->id,
                'amount' => (string) $deposit->amount,
                'status' => $deposit->status->value,
                'message' => 'En attente de validation. Le solde n’est pas encore crédité.',
            ],
        ], 201);
    }

    public function withdrawalQuote(Request $request, WithdrawalService $withdrawals)
    {
        $request->merge(['amount' => Money::normalizeInput($request->input('amount'))]);
        $data = $request->validate(['amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/']]);

        return response()->json($withdrawals->quote($data['amount']));
    }

    public function withdraw(Request $request, WithdrawalService $withdrawals)
    {
        $phone = PhoneNumber::normalize($request->input('phone'));
        $request->merge([
            'amount' => Money::normalizeInput($request->input('amount')),
            'phone' => $phone,
        ]);

        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'phone' => ['required', 'regex:/^\+243[0-9]{9}$/'],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        $withdrawal = $withdrawals->request(
            $request->user(),
            $data['amount'],
            PaymentMethod::from($data['method']),
            $data['phone'],
            $data['idempotency_key'],
        );

        return response()->json([
            'withdrawal' => [
                'id' => $withdrawal->id,
                'amount' => (string) $withdrawal->amount,
                'fee' => (string) $withdrawal->fee,
                'net_amount' => (string) $withdrawal->net_amount,
                'status' => $withdrawal->status->value,
            ],
        ], 201);
    }

    public function transactions(Request $request)
    {
        $entries = $request->user()->ledgerEntries()->latest()->paginate(30);

        return $entries->through(fn ($entry) => ApiPresenter::ledger($entry));
    }

    public function notifications(Request $request)
    {
        return $request->user()->notifications()->paginate(30);
    }
}
