<?php

namespace App\Http\Controllers;

use App\Enums\LedgerType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->string('type')->toString();

        $entries = $request->user()->ledgerEntries()
            ->when($type !== '' && LedgerType::tryFrom($type), fn ($query) => $query->where('type', $type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('transactions.index', [
            'entries' => $entries,
            'type' => $type,
            'types' => LedgerType::cases(),
        ]);
    }
}
