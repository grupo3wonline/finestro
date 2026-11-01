<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Indexable routes
    |--------------------------------------------------------------------------
    |
    | Only these named routes get `index, follow`. Every other page (auth,
    | app area, error pages) is rendered with `noindex, nofollow`. The sitemap
    | lists exactly these routes.
    |
    */

    'indexable_routes' => ['home', 'contact', 'faq', 'privacy', 'terms', 'cookies'],

    'default_title' => 'Gestão Financeira Pessoal',

    'default_description' => 'Finestro - Controle suas finanças pessoais de forma simples, inteligente e segura. Carteiras, transações, metas e simuladores financeiros.',

    /*
    | Social share image (1200x630 PNG in public/).
    */

    'og_image' => 'og-image.png',

];
