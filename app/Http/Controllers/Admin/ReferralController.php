<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LedgerStatus;
use App\Enums\LedgerType;
use App\Enums\ReferralTrigger;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\PlatformSetting;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ReferralProgressService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReferralController extends Controller
{
    public function index(ReferralProgressService $progress)
    {
        $commissions = ReferralCommission::query()->with(['referrer', 'referred'])->latest()->paginate(30);
        $top = User::query()
            ->where('role', UserRole::User)
            ->withSum('commissionsEarned as commissions_total', 'amount')
            ->orderByDesc('commissions_total')
            ->limit(5)
            ->get()
            ->filter(fn (User $user) => (float) $user->commissions_total > 0);

        $settings = PlatformSetting::current();

        return view('admin.referrals.index', [
            'commissions' => $commissions,
            'sponsors' => User::query()->where('role', UserRole::User)->has('referrals')->count(),
            'members' => User::query()->where('role', UserRole::User)->whereNotNull('referred_by_id')->count(),
            'paid' => Money::of(ReferralCommission::query()->sum('amount')),
            'bonus' => Money::of(LedgerEntry::query()->where('type', LedgerType::Bonus)->where('status', LedgerStatus::Completed)->sum('amount')),
            'levels' => $progress->rules($settings),
            'benefits' => config('zelvora.level_benefits'),
            'rate' => $settings->referral_rate_percent,
            'settings' => $settings,
            'top' => $top,
        ]);
    }

    public function update(Request $request, AuditService $audit, ReferralProgressService $progress)
    {
        $request->merge([
            'referral_rate_percent' => str_replace(',', '.', (string) $request->input('referral_rate_percent')),
        ]);

        $data = $request->validate([
            'referral_trigger' => ['required', Rule::enum(ReferralTrigger::class)],
            'referral_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $levels = $progress->rulesFromRequest($request);
        if ($levels === null) {
            throw ValidationException::withMessages([
                'level_pro' => 'Indiquez le nombre de membres actifs de chaque niveau.',
            ]);
        }

        $settings = PlatformSetting::current();
        $before = $settings->only([
            'referral_enabled',
            'referral_trigger',
            'referral_rate_percent',
            'referral_levels',
        ]);

        $settings->fill([
            ...$data,
            'referral_enabled' => $request->boolean('referral_enabled'),
            'referral_levels' => $levels,
        ])->save();

        $audit->record($request->user(), null, 'referral_settings_updated', null, null, null, 'Mise à jour des paramètres de parrainage', [
            'before' => $before,
            'after' => $settings->only(array_keys($before)),
        ]);

        return back()->with('success', 'Paramètres de parrainage enregistrés.');
    }
}
