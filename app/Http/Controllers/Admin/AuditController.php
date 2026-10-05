<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $action = trim((string) $request->input('action'));

        $logs = AuditLog::query()
            ->with(['admin', 'user'])
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->latest('created_at')
            ->paginate(40)
            ->withQueryString();

        return view('admin.audit.index', compact('logs', 'action'));
    }
}
