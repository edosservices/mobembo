<?php

namespace App\Http\Controllers;

use App\Exceptions\FinancialException;
use App\Services\TransferService;
use App\Support\Money;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function create(Request $request)
    {
        return view('transfers.create', [
            'quote' => null,
            'recipient' => null,
            'wallet' => $request->user()->wallet()->first(),
        ]);
    }

    public function preview(Request $request, TransferService $transfers)
    {
        [$name, $phone, $amount] = $this->fields($request);

        try {
            $recipient = $transfers->resolveRecipient($request->user(), $name, $phone);
            $quote = $transfers->quote($amount);
        } catch (FinancialException $exception) {
            return redirect()->route('transfers.create')->withInput()->with('error', $exception->getMessage());
        }

        return view('transfers.create', [
            'quote' => $quote,
            'recipient' => $recipient,
            'wallet' => $request->user()->wallet()->first(),
            'name' => $name,
            'phone' => $phone,
        ]);
    }

    public function store(Request $request, TransferService $transfers)
    {
        [$name, $phone, $amount] = $this->fields($request);
        $key = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
        ])['idempotency_key'];

        $transfers->send($request->user(), $name, $phone, $amount, $key);

        return redirect()->route('transactions.index', ['type' => 'transfer'])
            ->with('success', 'Le transfert est enregistré. Le bénéficiaire a reçu le montant, les frais restent à votre charge.');
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function fields(Request $request): array
    {
        $phone = PhoneNumber::normalize($request->input('phone'));
        $request->merge([
            'amount' => Money::normalizeInput($request->input('amount')),
            'phone' => $phone,
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        return [$data['name'], $data['phone'], (string) $data['amount']];
    }
}
