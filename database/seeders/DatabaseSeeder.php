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
        $settings = PlatformSetting::current();
        $readyDisclaimer = PlatformSetting::defaults()['legal_disclaimer'];

        $disclaimer = (string) $settings->legal_disclaimer;
        if (str_contains($disclaimer, 'ouverte au public') || str_contains($disclaimer, 'distribution réelle') || str_contains($disclaimer, 'ledger')) {
            $settings->forceFill(['legal_disclaimer' => $readyDisclaimer])->save();
        }

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
                'name' => 'Amina Mukendi',
                'password' => 'DemoUser!2026',
                'role' => UserRole::User,
                'referral_code' => 'ZLVDEMO',
                'referred_by_id' => $admin->id,
            ],
        );

        if ($demo->name === 'Amina Demo') {
            $demo->forceFill(['name' => 'Amina Mukendi'])->save();
        }

        if (! LedgerEntry::query()->where('idempotency_key', 'seed-demo-balance')->exists()) {
            app(WalletService::class)->credit($demo, '250.00', LedgerType::AdminAdjustment, [
                'description' => 'Ouverture de compte',
                'reference' => 'OUVERTURE',
                'idempotency_key' => 'seed-demo-balance',
                'created_by' => $admin->id,
            ]);
        }

        LedgerEntry::query()
            ->where('idempotency_key', 'seed-demo-balance')
            ->where('description', 'like', '%parcours%')
            ->update(['description' => 'Ouverture de compte']);

        Project::query()
            ->whereIn('slug', ['residence-gombe', 'immeuble-lubumbashi', 'terrain-goma'])
            ->update(['status' => ProjectStatus::Suspended->value]);

        foreach ($this->demoProjects() as $project) {
            $record = Project::query()->firstOrNew(['slug' => $project['slug']]);
            $hasInvestments = $record->exists && $record->investments()->exists();

            $record->fill([
                ...$project,
                'funded_amount' => $hasInvestments ? $record->funded_amount : $project['funded_amount'],
                'status' => $hasInvestments ? $record->status : $project['status'],
                'distribution_frequency' => DistributionFrequency::AtMaturity,
                'is_demo' => false,
                'currency' => 'USD',
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays($project['duration_days'])->toDateString(),
            ]);
            $record->save();
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function demoProjects(): array
    {
        $terms = 'Les revenus crédités apparaissent dans le portefeuille après leur distribution.';

        return [
            [
                'uuid' => '6f1c2a10-0a01-4a11-8a01-000000000001',
                'name' => 'Zelvora Urban Stay',
                'slug' => 'zelvora-urban-stay',
                'image_path' => 'images/projects/urban-stay.jpg',
                'category' => 'Boutique Hotel',
                'location' => 'Kinshasa, Gombe',
                'description' => 'Suites urbaines à Gombe pour des séjours courts. Minimum 10 $, durée 180 jours, rendement prévu 6,50 %.',
                'target_amount' => 25000,
                'funded_amount' => 9000,
                'min_investment' => 10,
                'duration_days' => 180,
                'expected_return_percent' => 6.5,
                'status' => ProjectStatus::Open,
                'economic_terms' => 'Estimation de 6,50 % sur 180 jours. '.$terms,
            ],
            [
                'uuid' => '6f1c2a10-0a01-4a11-8a01-000000000002',
                'name' => 'Kivu Pearl Residence',
                'slug' => 'kivu-pearl-residence',
                'image_path' => 'images/projects/kivu-pearl.jpg',
                'category' => 'Résidence immobilière',
                'location' => 'Goma, Nord-Kivu',
                'description' => 'Résidence au bord du Kivu, à Goma. Minimum 25 $, durée 240 jours, rendement prévu 7,00 %.',
                'target_amount' => 40000,
                'funded_amount' => 16000,
                'min_investment' => 25,
                'duration_days' => 240,
                'expected_return_percent' => 7,
                'status' => ProjectStatus::Active,
                'economic_terms' => 'Estimation de 7,00 % sur 240 jours. '.$terms,
            ],
            [
                'uuid' => '6f1c2a10-0a01-4a11-8a01-000000000003',
                'name' => 'Congo Vista',
                'slug' => 'congo-vista-apartments',
                'image_path' => 'images/projects/congo-vista.jpg',
                'category' => 'Appartements résidentiels',
                'location' => 'Kinshasa, Limete',
                'description' => 'Appartements résidentiels à Limete. Minimum 50 $, durée 270 jours, rendement prévu 7,50 %.',
                'target_amount' => 80000,
                'funded_amount' => 66400,
                'min_investment' => 50,
                'duration_days' => 270,
                'expected_return_percent' => 7.5,
                'status' => ProjectStatus::AlmostComplete,
                'economic_terms' => 'Estimation de 7,50 % sur 270 jours. '.$terms,
            ],
            [
                'uuid' => '6f1c2a10-0a01-4a11-8a01-000000000004',
                'name' => 'Royal Suites',
                'slug' => 'royal-gombe-suites',
                'image_path' => 'images/projects/royal-gombe.jpg',
                'category' => 'Luxury Suites',
                'location' => 'Kinshasa, Gombe',
                'description' => 'Suites destinées à une clientèle d’affaires à Gombe. Minimum 100 $, durée 300 jours, rendement prévu 8,00 %.',
                'target_amount' => 120000,
                'funded_amount' => 54000,
                'min_investment' => 100,
                'duration_days' => 300,
                'expected_return_percent' => 8,
                'status' => ProjectStatus::Open,
                'economic_terms' => 'Estimation de 8,00 % sur 300 jours. '.$terms,
            ],
            [
                'uuid' => '6f1c2a10-0a01-4a11-8a01-000000000005',
                'name' => 'Zelvora Residence',
                'slug' => 'zelvora-city-residence',
                'image_path' => 'images/projects/city-residence.jpg',
                'category' => 'Résidence premium',
                'location' => 'Kinshasa, Ngaliema',
                'description' => 'Résidence à Ngaliema. Minimum 200 $, durée 365 jours, rendement prévu 8,00 %.',
                'target_amount' => 150000,
                'funded_amount' => 60000,
                'min_investment' => 200,
                'duration_days' => 365,
                'expected_return_percent' => 8,
                'status' => ProjectStatus::Active,
                'economic_terms' => 'Estimation de 8,00 % sur 365 jours. '.$terms,
            ],
            [
                'uuid' => '6f1c2a10-0a01-4a11-8a01-000000000006',
                'name' => 'Congo River Hotel',
                'slug' => 'congo-river-hotel',
                'image_path' => 'images/projects/congo-river.jpg',
                'category' => 'Hôtel & Hospitality',
                'location' => 'Kinshasa, rives du fleuve',
                'description' => 'Hôtel sur les rives du fleuve Congo. Minimum 350 $, durée 365 jours, rendement prévu 8,50 %.',
                'target_amount' => 200000,
                'funded_amount' => 70000,
                'min_investment' => 350,
                'duration_days' => 365,
                'expected_return_percent' => 8.5,
                'status' => ProjectStatus::Open,
                'economic_terms' => 'Estimation de 8,50 % sur 365 jours. '.$terms,
            ],
            [
                'uuid' => '6f1c2a10-0a01-4a11-8a01-000000000007',
                'name' => 'Emerald Grand Residence',
                'slug' => 'emerald-grand-residence',
                'image_path' => 'images/projects/emerald-grand.jpg',
                'category' => 'Résidence haut standing',
                'location' => 'Lubumbashi, Haut-Katanga',
                'description' => 'Résidence haut standing à Lubumbashi. Minimum 500 $, durée 365 jours, rendement prévu 9,00 %.',
                'target_amount' => 250000,
                'funded_amount' => 212500,
                'min_investment' => 500,
                'duration_days' => 365,
                'expected_return_percent' => 9,
                'status' => ProjectStatus::AlmostComplete,
                'economic_terms' => 'Estimation de 9,00 % sur 365 jours. '.$terms,
            ],
            [
                'uuid' => '6f1c2a10-0a01-4a11-8a01-000000000008',
                'name' => 'Zelvora Executive Suites',
                'slug' => 'zelvora-executive-suites',
                'image_path' => 'images/projects/executive-suites.jpg',
                'category' => 'Appartements & Suites',
                'location' => 'Kinshasa, Gombe',
                'description' => 'Appartements et suites à Gombe. Minimum 750 $, durée 365 jours, rendement prévu 8,00 %.',
                'target_amount' => 300000,
                'funded_amount' => 90000,
                'min_investment' => 750,
                'duration_days' => 365,
                'expected_return_percent' => 8,
                'status' => ProjectStatus::Active,
                'economic_terms' => 'Estimation de 8,00 % sur 365 jours. '.$terms,
            ],
            [
                'uuid' => '6f1c2a10-0a01-4a11-8a01-000000000009',
                'name' => 'Golden River Resort',
                'slug' => 'golden-river-resort',
                'image_path' => 'images/projects/golden-river.jpg',
                'category' => 'Resort & Hospitality',
                'location' => 'Kisangani, Tshopo',
                'description' => 'Resort à Kisangani. Minimum 1 000 $, durée 365 jours, rendement prévu 9,00 %.',
                'target_amount' => 500000,
                'funded_amount' => 125000,
                'min_investment' => 1000,
                'duration_days' => 365,
                'expected_return_percent' => 9,
                'status' => ProjectStatus::Open,
                'economic_terms' => 'Estimation de 9,00 % sur 365 jours. '.$terms,
            ],
            [
                'uuid' => '6f1c2a10-0a01-4a11-8a01-000000000010',
                'name' => 'Zelvora Grand Palace',
                'slug' => 'zelvora-grand-palace',
                'image_path' => 'images/projects/grand-palace.jpg',
                'category' => 'Luxury Hotel & Residences',
                'location' => 'Kinshasa, RDC',
                'description' => 'Hôtel et résidences à Kinshasa. Minimum 1 500 $, durée 365 jours, rendement prévu 8,00 %.',
                'target_amount' => 100000,
                'funded_amount' => 78000,
                'min_investment' => 1500,
                'duration_days' => 365,
                'expected_return_percent' => 8,
                'status' => ProjectStatus::Open,
                'economic_terms' => 'Estimation de 8,00 % sur 365 jours, selon les conditions du projet. '.$terms,
            ],
        ];
    }
}
