<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReferralTrigger;
use App\Http\Controllers\Controller;
use App\Models\PaymentDestination;
use App\Models\PlatformSetting;
use App\Services\AuditService;
use App\Services\ReferralProgressService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function edit(ReferralProgressService $progress)
    {
        return view('admin.settings.edit', [
            'settings' => PlatformSetting::current(),
            'levels' => $progress->rules(),
            'destinations' => PaymentDestination::query()->latest()->get(),
        ]);
    }

    public function update(Request $request, AuditService $audit, ReferralProgressService $progress)
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
            'mpesa_holder' => ['nullable', 'string', 'max:80'],
            'airtel_number' => ['nullable', 'string', 'max:32'],
            'airtel_holder' => ['nullable', 'string', 'max:80'],
            'orange_number' => ['nullable', 'string', 'max:32'],
            'orange_holder' => ['nullable', 'string', 'max:80'],
        ]);

        foreach (['mpesa_number', 'mpesa_holder', 'airtel_number', 'airtel_holder', 'orange_number', 'orange_holder'] as $field) {
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
            'referral_levels',
            'otp_enabled',
            'kyc_required_for_withdrawal',
            'mpesa_number',
            'mpesa_holder',
            'airtel_number',
            'airtel_holder',
            'orange_number',
            'orange_holder',
            'whatsapp_url',
            'telegram_url',
        ]);

        $settings->fill([
            ...$data,
            'referral_enabled' => $request->boolean('referral_enabled'),
            'otp_enabled' => $request->boolean('otp_enabled'),
            'kyc_required_for_withdrawal' => $request->boolean('kyc_required_for_withdrawal'),
            'referral_levels' => $progress->rulesFromRequest($request) ?? $settings->referral_levels,
            ...$this->communityLinks($request),
        ])->save();

        $audit->record($request->user(), null, 'settings_updated', null, null, null, 'Mise à jour des paramètres de la plateforme', [
            'before' => $before,
            'after' => $settings->only(array_keys($before)),
        ]);

        return back()->with('success', 'Paramètres enregistrés.');
    }

    /**
     * @return array<string, string|null>
     */
    private function communityLinks(Request $request): array
    {
        if (! $request->exists('whatsapp_url') && ! $request->exists('telegram_url')) {
            return [];
        }

        $links = [];
        foreach (['whatsapp_url', 'telegram_url'] as $field) {
            $value = trim((string) $request->input($field));
            $request->merge([$field => $value === '' ? null : $value]);
        }

        $data = $request->validate([
            'whatsapp_url' => ['nullable', 'regex:/^https?:\/\/\S+$/i', 'max:255'],
            'telegram_url' => ['nullable', 'regex:/^https?:\/\/\S+$/i', 'max:255'],
        ]);

        foreach (['whatsapp_url', 'telegram_url'] as $field) {
            $links[$field] = $data[$field] ?? null;
        }

        return $links;
    }
}
