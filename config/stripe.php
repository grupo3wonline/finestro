<?php

declare(strict_types=1);

return [
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    'currency' => 'BRL',

    /*
    | Optional promotion. Only shown/charged when every field is valid and
    | ends_at is in the future (see App\Services\PlanPricing::isPromoActive).
    */
    'promo' => [
        'enabled' => (bool) env('STRIPE_PROMO_ENABLED', false),
        'price' => env('STRIPE_PROMO_PRICE'),
        'ends_at' => env('STRIPE_PROMO_ENDS_AT'),
        'stripe_price_id' => env('STRIPE_PROMO_PRICE_ID'),
        'label' => env('STRIPE_PROMO_LABEL', 'Oferta especial'),
    ],

    'plans' => [
        'free' => [
            'name' => 'Básico',
            'description' => 'Ideal para quem está começando a organizar suas finanças pessoais.',
            'price' => 0,
            'stripe_price_id' => null,
            'features' => [
                '1 carteira (pessoal ou empresarial)',
                '100 transações/mês',
                '2 metas financeiras',
                '5 categorias personalizáveis',
                'Dashboard com resumo mensal',
                'Calculadoras financeiras gratuitas',
                'Cotação de moedas em tempo real',
                'Teste de perfil financeiro',
                'Teste de perfil investidor',
            ],
            'limitations' => [
                'Simuladores financeiros',
                'Relatórios com exportação CSV',
                'Inteligência Artificial',
                'Parecer técnico com IA',
                'Planejamento financeiro com IA',
                'Acesso à API REST',
                'Suporte prioritário',
            ],
        ],
        'professional' => [
            'name' => 'Profissional',
            'description' => 'Para quem leva suas finanças a sério e quer o controle total com inteligência artificial.',
            'price' => (float) env('STRIPE_PROFESSIONAL_PRICE', 29.90),
            'interval' => 'month',
            'stripe_price_id' => env('STRIPE_PROFESSIONAL_PRICE_ID'),
            'features' => [
                'Carteiras ilimitadas (pessoais e empresariais)',
                'Compartilhamento de carteiras empresariais',
                'Transações ilimitadas',
                'Metas ilimitadas com acompanhamento',
                'Categorias ilimitadas',
                'Simuladores financeiros avançados',
                'Relatórios completos com exportação CSV',
                'Análise financeira com IA',
                'Parecer técnico financeiro com IA',
                'Planejamento financeiro personalizado com IA',
                'Análise de metas com IA',
                'Plano por objetivo com IA',
                'Calculadoras e cotações gratuitas',
                'Acesso à API REST com tokens pessoais',
                'Suporte prioritário',
            ],
            'highlights' => [
                'Tudo do plano Básico',
                '+ Sem limites em carteiras, transações e metas',
                '+ 5 funcionalidades de Inteligência Artificial',
                '+ Relatórios detalhados exportáveis',
                '+ Simuladores de investimento, aposentadoria e orçamento',
            ],
        ],
    ],
];
