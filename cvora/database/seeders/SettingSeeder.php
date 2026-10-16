<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::putValue('features.registration', true, 'features');
        Setting::putValue('features.ai', true, 'features');
        Setting::putValue('features.exports', true, 'features');
        Setting::putValue('features.imports', true, 'features');
        Setting::putValue('features.payments', true, 'features');
        Setting::putValue('auth.require_email_verification', false, 'auth');
        Setting::putValue('contact.email', 'contact@cvora.app', 'contact');
        Setting::putValue('ai.model', '', 'ai');
        Setting::putValue('ai.base_url', '', 'ai');
    }
}
