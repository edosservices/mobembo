<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'flags' => [
                'features.registration' => Setting::enabled('features.registration', true),
                'features.ai' => Setting::enabled('features.ai', true),
                'features.exports' => Setting::enabled('features.exports', true),
                'features.imports' => Setting::enabled('features.imports', true),
                'features.payments' => Setting::enabled('features.payments', true),
                'auth.require_email_verification' => Setting::enabled('auth.require_email_verification', false),
            ],
            'aiModel' => Setting::getValue('ai.model', ''),
            'aiBaseUrl' => Setting::getValue('ai.base_url', ''),
            'contactEmail' => Setting::getValue('contact.email', ''),
            'aiConfigured' => is_string(config('cvora.ai.key')) && config('cvora.ai.key') !== '',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ai_model' => ['nullable', 'string', 'max:80'],
            'ai_base_url' => ['nullable', 'url', 'max:200'],
            'contact_email' => ['nullable', 'email', 'max:160'],
        ]);

        foreach ([
            'features.registration' => 'features_registration',
            'features.ai' => 'features_ai',
            'features.exports' => 'features_exports',
            'features.imports' => 'features_imports',
            'features.payments' => 'features_payments',
            'auth.require_email_verification' => 'auth_require_email_verification',
        ] as $key => $input) {
            Setting::putValue($key, $request->boolean($input), str_starts_with($key, 'auth.') ? 'auth' : 'features');
        }

        Setting::putValue('ai.model', $data['ai_model'] ?? '', 'ai');
        Setting::putValue('ai.base_url', $data['ai_base_url'] ?? '', 'ai');
        Setting::putValue('contact.email', $data['contact_email'] ?? '', 'contact');

        return back()->with('status', 'Configuration enregistrée. La clé API reste dans l’environnement.');
    }
}
