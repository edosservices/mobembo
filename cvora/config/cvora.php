<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Intelligence artificielle
    |--------------------------------------------------------------------------
    | Compatible avec toute API de type OpenAI (chat/completions).
    | La clé reste dans l'environnement et n'est jamais exposée au navigateur.
    */

    'ai' => [
        'key' => env('AI_API_KEY'),
        'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('AI_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('AI_TIMEOUT', 25),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paiements
    |--------------------------------------------------------------------------
    | sandbox : aucun argent n'est débité. Le succès passe uniquement
    | par une notification signée.
    | unipay : prestataire réel, activé seulement si les identifiants sont présents.
    */

    'payment' => [
        'mode' => env('PAYMENT_MODE', 'sandbox'),
        'sandbox_secret' => env('SANDBOX_WEBHOOK_SECRET'),
        'unipay' => [
            'base_url' => env('UNIPAY_BASE_URL'),
            'merchant_id' => env('UNIPAY_MERCHANT_ID'),
            'api_key' => env('UNIPAY_API_KEY'),
            'webhook_secret' => env('UNIPAY_WEBHOOK_SECRET'),
        ],
    ],

];
