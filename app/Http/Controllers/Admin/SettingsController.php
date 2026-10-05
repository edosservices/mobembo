<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReferralTrigger;
use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\AuditService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit', ['settings' => PlatformSetting::current()]);
    }

    public function update(Request $request, AuditService $audit)
    {
        $request->merge([
            'withdrawal_fee_percent' => str_replace(',', '.', (string) $request->input('withdrawal_fee_percent')),
            'withdrawal_fee_fixed' => Money::normalizeInput($request->input('withdrawal_fee_fixed')),
            'withdrawal_min' => Money::normalizeInput($request->input('withdrawal_min')),
            'withdrawal_max' => Money::normalizeInput($request->input('withdrawal_max')),
            'referral_rate_percent' => str_replace(',', '.', (string) $request->input('referral_rate_percent')),
        ]);

        $data = $request->validate([
            'withdrawal_fee_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'withdrawal_fee_fixed' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'withdrawal_min' => ['required', 'regex:/^\d+(\.\d{1,2})?$/'],
            'withdrawal_max' => ['required', 'regex:/^\d+(\.\d{1,2})?$/', 'gte:withdrawal_min'],
            'referral_trigger' => ['required', Rule::enum(ReferralTrigger::class)],
            'referral_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'legal_disclaimer' => ['required', 'string', 'max:5000'],
            'mpesa_number' => ['nullable', 'string', 'max:32'],
            'airtel_number' => ['nullable', 'string', 'max:32'],
            'orange_number' => ['nullable', 'string', 'max:32'],
        ]);

        foreach (['mpesa_number', 'airtel_number', 'orange_number'] as $field) {
            $data[$field] = trim((string) ($data[$field] ?? '')) ?: null;
        }

        $settings = PlatformSetting::current();
        $before = $settings->only([
            'withdrawal_fee_percent',
            'withdrawal_fee_fixed',
            'withdrawal_min',
            'withdrawal_max',
            'referral_enabled',
            'referral_trigger',
            'referral_rate_percent',
            'otp_enabled',
            'kyc_required_for_withdrawal',
            'mpesa_number',
            'airtel_number',
            'orange_number',
        ]);

        $settings->fill([
            ...$data,
            'referral_enabled' => $request->boolean('referral_enabled'),
            'otp_enabled' => $request->boolean('otp_enabled'),
            'kyc_required_for_withdrawal' => $request->boolean('kyc_required_for_withdrawal'),
        ])->save();

        $audit->record($request->user(), null, 'settings_updated', null, null, null, 'Mise à jour des paramètres de la plateforme', [
            'before' => $before,
            'after' => $settings->only(array_keys($before)),
        ]);

        return back()->with('success', 'Paramètres enregistrés.');
    }
}
