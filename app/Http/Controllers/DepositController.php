<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\PlatformSetting;
use App\Services\DepositService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepositController extends Controller
{
    public function create(Request $request)
    {
        $deposits = $request->user()->deposits()->latest()->limit(8)->get();
        $settings = PlatformSetting::current();

        return view('deposits.create', compact('deposits', 'settings'));
    }

    public function store(Request $request, DepositService $deposits)
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
            $deposits->submit(
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

        return redirect()->route('deposits.create')->with('success', 'Dépôt envoyé. Le solde ne bougera qu’après validation d’un administrateur.');
    }
}
