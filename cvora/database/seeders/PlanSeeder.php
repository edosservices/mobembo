<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'gratuit',
                'name' => 'Gratuit',
                'description' => 'Un CV simple, deux modèles et un aperçu. Les quotas se règlent ici, pas dans le code.',
                'price_cents' => 0,
                'currency' => 'CDF',
                'interval' => 'none',
                'sort_order' => 1,
                'limits' => [
                    'resumes' => 1,
                    'cover_letters' => 0,
                    'ai_credits_monthly' => 5,
                    'templates' => ['moderne', 'classique'],
                    'exports' => [],
                    'import' => false,
                    'versions' => false,
                    'job_adapt' => false,
                    'applications' => 3,
                ],
            ],
            [
                'slug' => 'premium',
                'name' => 'Premium',
                'description' => 'Modèles supplémentaires, export PDF et Word, importation et lettres. Le prix est fixé par l’administrateur.',
                'price_cents' => null,
                'currency' => 'CDF',
                'interval' => 'month',
                'sort_order' => 2,
                'limits' => [
                    'resumes' => 5,
                    'cover_letters' => 15,
                    'ai_credits_monthly' => 80,
                    'templates' => '*',
                    'exports' => ['pdf', 'docx'],
                    'import' => true,
                    'versions' => true,
                    'job_adapt' => false,
                    'applications' => 30,
                ],
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'description' => 'Volumes plus élevés, historique et adaptation du CV à une offre. Le prix est fixé par l’administrateur.',
                'price_cents' => null,
                'currency' => 'CDF',
                'interval' => 'month',
                'sort_order' => 3,
                'limits' => [
                    'resumes' => null,
                    'cover_letters' => null,
                    'ai_credits_monthly' => 300,
                    'templates' => '*',
                    'exports' => ['pdf', 'docx'],
                    'import' => true,
                    'versions' => true,
                    'job_adapt' => true,
                    'applications' => null,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(['slug' => $plan['slug']], $plan + [
                'is_active' => true,
                'is_public' => true,
            ]);
        }
    }
}
