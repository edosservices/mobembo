<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportController extends Controller
{
    public function leave(Request $request, AuditService $audit)
    {
        $adminId = $request->session()->get('impersonator_id');
        $subjectId = $request->session()->get('impersonated_user_id');
        abort_unless($adminId, 403);

        $admin = User::query()->find($adminId);
        abort_unless($admin && $admin->isAdmin() && $admin->status === AccountStatus::Active, 403);

        $subject = $subjectId ? User::query()->find($subjectId) : null;

        Auth::login($admin);
        $request->session()->regenerate();
        $request->session()->forget(['impersonator_id', 'impersonated_user_id']);

        $audit->record($admin, $subject, 'support_access_ended', null, null, null, 'Fin du dépannage du compte client.');

        return redirect()
            ->route($subject ? 'admin.users.show' : 'admin.users.index', $subject)
            ->with('success', 'Vous êtes de retour dans l’administration.');
    }
}
