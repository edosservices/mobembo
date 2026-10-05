<?php

return [
    'levels' => [
        ['key' => 'starter', 'name' => 'STARTER', 'min_active' => 0],
        ['key' => 'pro', 'name' => 'PRO', 'min_active' => 5],
        ['key' => 'elite', 'name' => 'ELITE', 'min_active' => 10],
        ['key' => 'vip', 'name' => 'VIP', 'min_active' => 25],
    ],

    'level_benefits' => [
        'starter' => ['Badge STARTER', 'Suivi de votre équipe'],
        'pro' => ['Badge PRO', 'Suivi de votre équipe'],
        'elite' => ['Badge ELITE', 'Suivi de votre équipe'],
        'vip' => ['Badge VIP', 'Suivi de votre équipe'],
    ],

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrateur ZELVORA'),
        'phone' => env('ADMIN_PHONE', '+243810000001'),
        'password' => env('ADMIN_PASSWORD', 'ChangeMe!Zelvora2026'),
    ],
];
