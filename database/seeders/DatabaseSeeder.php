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
                'is_demo' => true,
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
        $terms = 'Le pourcentage affiché est une estimation sur la durée du projet. Il n’est pas garanti et n’est jamais crédité automatiquement. Une distribution réelle n’existe que si l’administration enregistre un produit effectif du projet. Le montant déjà financé des fiches de démonstration est un instantané illustratif : il ne correspond pas à un solde client.';

        return [
            [
                'uuid' => '6f1c2a10-0a01-4a11-8a01-000000000001',
                'name' => 'Zelvora Urban Stay',
                'slug' => 'zelvora-urban-stay',
                'image_path' => 'images/projects/urban-stay.jpg',
                'category' => 'Boutique Hotel',
                'location' => 'Kinshasa, Gombe',
                'description' => 'Projet de démonstration : un boutique-hôtel urbain, pensé pour des séjours courts au centre de Kinshasa. Les visuels sont illustratifs et ne représentent pas un établissement détenu par ZELVORA.',
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
                'description' => 'Résidence de démonstration au bord du lac Kivu. La fiche sert à présenter une opportunité résidentielle, ses conditions et sa progression de financement.',
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
                'name' => 'Congo Vista Apartments',
                'slug' => 'congo-vista-apartments',
                'image_path' => 'images/projects/congo-vista.jpg',
                'category' => 'Appartements résidentiels',
                'location' => 'Kinshasa, Limete',
                'description' => 'Ensemble d’appartements résidentiels de démonstration. Le financement affiché illustre une collecte déjà bien avancée, sans créer de revenu pour les comptes.',
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
                'name' => 'Royal Gombe Suites',
                'slug' => 'royal-gombe-suites',
                'image_path' => 'images/projects/royal-gombe.jpg',
                'category' => 'Luxury Suites',
                'location' => 'Kinshasa, Gombe',
                'description' => 'Suites de démonstration destinées à une clientèle d’affaires. L’image montre un intérieur hôtelier générique, pas un actif identifié de ZELVORA.',
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
                'name' => 'Zelvora City Residence',
                'slug' => 'zelvora-city-residence',
                'image_path' => 'images/projects/city-residence.jpg',
                'category' => 'Résidence premium',
                'location' => 'Kinshasa, Ngaliema',
                'description' => 'Résidence premium de démonstration à Ngaliema. La page détaille l’objectif, le reste à financer et les conditions avant tout investissement.',
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
                'description' => 'Projet hôtelier de démonstration face au fleuve. L’exploitation, si elle était réelle, ne produirait un revenu qu’après enregistrement des recettes.',
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
                'description' => 'Résidence haut standing de démonstration à Lubumbashi. La progression élevée indique une collecte presque close, encore ouverte au minimum indiqué.',
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
                'description' => 'Appartements et suites de démonstration pour des séjours longs. Le rendement prévu reste une hypothèse liée aux conditions du projet.',
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
                'description' => 'Resort de démonstration sur le fleuve, présenté pour explorer une opportunité hospitality. Aucune photographie n’identifie un hôtel réel comme propriété de ZELVORA.',
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
                'description' => 'Projet de démonstration combinant hôtel de luxe et résidences. L’objectif, le montant déjà illustré et le reste à financer sont affichés avant toute confirmation.',
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
