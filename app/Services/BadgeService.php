<?php

namespace App\Services;

class BadgeService
{
    /**
     * @param  array<string, mixed>  $progress
     * @return list<array{key: string, name: string, description: string, icon: string, unlocked: bool}>
     */
    public function evaluate(array $progress): array
    {
        $definitions = [
            [
                'key' => 'first_investment',
                'name' => 'Premier investissement',
                'description' => 'Un premier investissement est confirmé.',
                'icon' => '◆',
                'unlocked' => ($progress['investments_count'] ?? 0) >= 1,
            ],
            [
                'key' => 'active_investor',
                'name' => 'Investisseur actif',
                'description' => 'Au moins un investissement est en cours.',
                'icon' => '◇',
                'unlocked' => ($progress['active_investments'] ?? 0) >= 1,
            ],
            [
                'key' => 'five_active',
                'name' => '5 filleuls actifs',
                'description' => 'Cinq membres parrainés ont un dépôt approuvé.',
                'icon' => '▣',
                'unlocked' => ($progress['active'] ?? 0) >= 5,
            ],
            [
                'key' => 'ten_active',
                'name' => '10 filleuls actifs',
                'description' => 'Dix membres parrainés ont un dépôt approuvé.',
                'icon' => '▣',
                'unlocked' => ($progress['active'] ?? 0) >= 10,
            ],
            [
                'key' => 'elite',
                'name' => 'Elite',
                'description' => 'Le niveau ELITE est atteint.',
                'icon' => '◈',
                'unlocked' => in_array($progress['level']['key'] ?? '', ['elite', 'vip'], true),
            ],
            [
                'key' => 'vip',
                'name' => 'VIP',
                'description' => 'Le niveau VIP est atteint.',
                'icon' => '◈',
                'unlocked' => ($progress['level']['key'] ?? '') === 'vip',
            ],
            [
                'key' => 'ambassador',
                'name' => 'Top ambassadeur',
                'description' => 'Vingt-cinq membres actifs sont parrainés.',
                'icon' => '★',
                'unlocked' => ($progress['active'] ?? 0) >= 25,
            ],
        ];

        return $definitions;
    }
}
