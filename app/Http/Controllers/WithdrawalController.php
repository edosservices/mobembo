<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Models\PlatformSetting;
use App\Services\WithdrawalService;
use App\Support\BusinessCalendar;
use App\Support\Money;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WithdrawalController extends Controller
{
    public function create(Request $request, WithdrawalService $withdrawals)
    {
        $settings = PlatformSetting::current();
        $quote = null;

        if ($request->filled('amount')) {
            try {
                $quote = $withdrawals->quote(Money::normalizeInput($request->input('amount')));
            } catch (\InvalidArgumentException) {
                $quote = null;
            }
        }

        $recent = $request->user()->withdrawals()->latest()->limit(8)->get();

        return view('withdrawals.create', compact('settings', 'quote', 'recent'));
    }

    public function store(Request $request, WithdrawalService $withdrawals)
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

        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => 'Indiquez un numéro mobile RDC valide.',
            ]);
        }

        $withdrawal = $withdrawals->request(
            $request->user(),
            $data['amount'],
            PaymentMethod::from($data['method']),
            $phone,
            $data['idempotency_key'],
        );

        $timing = BusinessCalendar::withdrawalsOpen()
            ? 'La vérification est en cours et peut prendre de quelques minutes à quelques heures.'
            : 'Votre demande est enregistrée. La vérification reprend lundi.';

        return redirect()->route('withdrawals.create')->with(
            'success',
            'Retrait effectué. Montant '.money($withdrawal->amount).', frais '.money($withdrawal->fee).', net à recevoir '.money($withdrawal->net_amount).'. '.$timing,
        );
    }
}
