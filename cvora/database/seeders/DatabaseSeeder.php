<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Accounts\AccountProvisioner;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            ResumeTemplateSeeder::class,
            SettingSeeder::class,
        ]);

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            $this->command?->warn('Aucun administrateur créé : renseignez ADMIN_EMAIL et ADMIN_PASSWORD.');

            return;
        }

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrateur CVora'),
                'password' => $password,
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        app(AccountProvisioner::class)->provision($admin);
    }
}
