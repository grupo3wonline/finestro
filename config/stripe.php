<?php

return [
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    'plans' => [
        'free' => [
            'name' => 'Básico',
            'price' => 0,
            'stripe_price_id' => null,
            'features' => [
                '1 carteira',
                '100 transações/mês',
                '2 metas financeiras',
                '5 categorias',
                'Dashboard básico',
            ],
        ],
        'professional' => [
            'name' => 'Profissional',
            'price' => 29.90,
            'stripe_price_id' => env('STRIPE_PROFESSIONAL_PRICE_ID'),
            'features' => [
                'Carteiras ilimitadas',
                'Transações ilimitadas',
                'Metas ilimitadas',
                'Categorias ilimitadas',
                'Simuladores financeiros',
                'Relatórios com exportação CSV',
                'Análise financeira com IA',
                'Plano financeiro com IA',
                'Análise de metas com IA',
                'Suporte prioritário',
            ],
        ],
    ],
];
