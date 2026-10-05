<?php

namespace Database\Seeders;

use App\Enums\DistributionFrequency;
use App\Enums\LedgerType;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\LedgerEntry;
use App\Models\PlatformSetting;
use App\Models\Project;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        PlatformSetting::current();

        $admin = User::query()->firstOrCreate(
            ['phone' => config('zelvora.admin.phone')],
            [
                'name' => config('zelvora.admin.name'),
                'password' => config('zelvora.admin.password'),
                'role' => UserRole::Admin,
                'referral_code' => 'ZLVADMIN',
            ],
        );

        $demo = User::query()->firstOrCreate(
            ['phone' => '+243810000002'],
            [
                'name' => 'Amina Demo',
                'password' => 'DemoUser!2026',
                'role' => UserRole::User,
                'referral_code' => 'ZLVDEMO',
                'referred_by_id' => $admin->id,
            ],
        );

        if (! LedgerEntry::query()->where('idempotency_key', 'seed-demo-balance')->exists()) {
            app(WalletService::class)->credit($demo, '250.00', LedgerType::AdminAdjustment, [
                'description' => 'Solde de démonstration — à retirer avant une ouverture publique',
                'reference' => 'SEED-DEMO',
                'idempotency_key' => 'seed-demo-balance',
                'created_by' => $admin->id,
            ]);
        }

        $projects = [
            [
                'name' => 'Résidence Gombe',
                'slug' => 'residence-gombe',
                'description' => 'Immeuble résidentiel de démonstration à Gombe, Kinshasa. Les chiffres servent à parcourir la plateforme.',
                'location' => 'Kinshasa, Gombe',
                'category' => 'Résidentiel',
                'target_amount' => 100000,
                'min_investment' => 5,
                'duration_days' => 180,
                'expected_return_percent' => 8,
                'status' => ProjectStatus::Active,
                'economic_terms' => 'Le rendement de 8 % est une estimation sur 180 jours, non garantie. Aucun revenu n’est crédité tant qu’un loyer ou une autre recette réelle n’est pas enregistré par l’administration.',
            ],
            [
                'name' => 'Immeuble commercial Lubumbashi',
                'slug' => 'immeuble-lubumbashi',
                'description' => 'Projet commercial de démonstration. Le financement et les distributions restent des opérations explicites.',
                'location' => 'Lubumbashi',
                'category' => 'Commercial',
                'target_amount' => 75000,
                'min_investment' => 10,
                'duration_days' => 365,
                'expected_return_percent' => 11,
                'status' => ProjectStatus::Active,
                'economic_terms' => 'Estimation de 11 % sur 12 mois, liée à des loyers commerciaux. Le pourcentage n’est pas un versement automatique.',
            ],
            [
                'name' => 'Terrain aménagé Goma',
                'slug' => 'terrain-goma',
                'description' => 'Projet en brouillon. Il n’accepte pas encore d’investissement.',
                'location' => 'Goma',
                'category' => 'Terrain',
                'target_amount' => 40000,
                'min_investment' => 5,
                'duration_days' => 240,
                'expected_return_percent' => 9,
                'status' => ProjectStatus::Draft,
                'economic_terms' => 'Brouillon de démonstration. Le rendement prévu sera affiché seulement lorsque le projet sera ouvert.',
            ],
        ];

        foreach ($projects as $project) {
            Project::query()->firstOrCreate(
                ['slug' => $project['slug']],
                [
                    ...$project,
                    'distribution_frequency' => DistributionFrequency::AtMaturity,
                    'is_demo' => true,
                    'currency' => 'USD',
                ],
            );
        }
    }
}
