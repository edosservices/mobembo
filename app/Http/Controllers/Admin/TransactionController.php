<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LedgerType;
use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->string('type')->toString();
        $q = trim((string) $request->input('q'));

        $entries = LedgerEntry::query()
            ->with('user')
            ->when(LedgerType::tryFrom($type), fn ($query) => $query->where('type', $type))
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('user', function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.transactions.index', [
            'entries' => $entries,
            'type' => $type,
            'q' => $q,
            'types' => LedgerType::cases(),
        ]);
    }

    public function adjustments()
    {
        $entries = LedgerEntry::query()
            ->with('user')
            ->whereIn('type', [LedgerType::Bonus, LedgerType::AdminAdjustment])
            ->latest()
            ->paginate(30);

        return view('admin.adjustments.index', ['entries' => $entries]);
    }
}
