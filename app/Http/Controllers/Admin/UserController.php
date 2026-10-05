<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountService;
use App\Services\AuditService;
use App\Services\PortfolioService;
use App\Support\Money;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q'));

        $users = User::query()
            ->where('role', UserRole::User)
            ->with(['wallet', 'referrer'])
            ->withCount('referrals as team_count')
            ->withSum(['ledgerEntries as returns_total' => fn ($query) => $query->where('type', 'investment_return')->where('status', 'completed')], 'amount')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'q' => $q,
            'heading' => $request->routeIs('admin.support') ? 'Support clients' : 'Utilisateurs',
        ]);
    }

    public function show(User $user, PortfolioService $portfolio)
    {
        abort_if($user->isAdmin(), 404);
        $user->load(['wallet', 'referrer', 'kycDocuments']);

        return view('admin.users.show', [
            'user' => $user,
            'summary' => $portfolio->summary($user),
            'transactions' => $user->ledgerEntries()->latest()->limit(12)->get(),
            'deposits' => $user->deposits()->latest()->limit(8)->get(),
            'withdrawals' => $user->withdrawals()->latest()->limit(8)->get(),
            'investments' => $user->investments()->with('project')->latest('invested_at')->limit(8)->get(),
            'audits' => $user->hasMany(\App\Models\AuditLog::class)->latest('created_at')->limit(12)->get(),
        ]);
    }

    public function update(Request $request, User $user, AuditService $audit)
    {
        abort_if($user->isAdmin(), 404);
        $phone = PhoneNumber::normalize($request->input('phone'));

        if (! $phone) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phone' => 'Indiquez un numéro mobile RDC valide, par exemple 0812345678.',
            ]);
        }

        $request->merge(['phone' => $phone]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
        ]);

        $user->forceFill($data)->save();
        $audit->record($request->user(), $user, 'user_profile_updated', null, null, null, 'Nom ou numéro modifié depuis l’administration.');

        return back()->with('success', 'La fiche du client a été mise à jour.');
    }

    public function impersonate(Request $request, User $user, AuditService $audit)
    {
        abort_if($user->isAdmin(), 403);

        if ($user->status !== AccountStatus::Active) {
            return back()->with('error', 'Ce compte est bloqué. Débloquez-le avant d’ouvrir le dépannage.');
        }

        $admin = $request->user();
        $audit->record($admin, $user, 'support_access_started', null, null, null, 'Ouverture du compte client pour dépannage.');

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('impersonator_id', $admin->id);
        $request->session()->put('impersonated_user_id', $user->id);

        return redirect()->route('dashboard')->with('success', 'Vous consultez le compte de '.$user->name.'.');
    }

    public function block(Request $request, User $user, AccountService $accounts)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $accounts->block($user, $request->user(), $data['reason']);

        return back()->with('success', 'Compte bloqué.');
    }

    public function unblock(Request $request, User $user, AccountService $accounts)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $accounts->unblock($user, $request->user(), $data['reason']);

        return back()->with('success', 'Compte débloqué.');
    }

    public function bonus(Request $request, User $user, AccountService $accounts)
    {
        $request->merge(['amount' => Money::normalizeInput($request->input('amount'))]);
        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'idempotency_key' => ['required', 'uuid'],
        ]);
        $accounts->bonus($user, $request->user(), $data['amount'], $data['reason'], $data['idempotency_key']);

        return back()->with('success', 'Bonus enregistré dans l’historique des opérations.');
    }

    public function adjust(Request $request, User $user, AccountService $accounts)
    {
        $request->merge(['amount' => Money::normalizeInput($request->input('amount'))]);
        $data = $request->validate([
            'amount' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'direction' => ['required', Rule::in(['credit', 'debit'])],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        $signed = $data['direction'] === 'debit' ? Money::sub('0', $data['amount']) : $data['amount'];
        $accounts->adjust($user, $request->user(), $signed, $data['reason'], $data['idempotency_key']);

        return back()->with('success', 'Ajustement enregistré dans l’historique des opérations.');
    }

    public function password(Request $request, User $user, AccountService $accounts)
    {
        $password = $accounts->resetPassword($user, $request->user());

        return back()->with('temporary_password', $password)->with('success', 'Mot de passe temporaire créé. Il ne sera plus affiché ensuite.');
    }

    public function kyc(Request $request, User $user, AccountService $accounts)
    {
        $data = $request->validate([
            'kyc_status' => ['required', Rule::enum(KycStatus::class)],
            'kyc_note' => ['nullable', 'string', 'max:500'],
        ]);

        $accounts->reviewKyc($user, $request->user(), KycStatus::from($data['kyc_status']), $data['kyc_note'] ?? null);

        return back()->with('success', 'Statut KYC mis à jour.');
    }
}
