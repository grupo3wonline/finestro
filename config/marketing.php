<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Grupo 3W Online CTA
    |--------------------------------------------------------------------------
    |
    | Target of every "Grupo 3W Online" call to action. `monthly_slots` is the
    | real number of new projects accepted per month (honest scarcity copy),
    | confirmed by the business. No default: anything but a positive integer
    | is null and the CTA hides the capacity line.
    |
    */

    'cta' => [
        'url' => 'https://www.3wonline.com.br',
        'monthly_slots' => filter_var(env('MARKETING_CTA_MONTHLY_SLOTS'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
            'flags' => FILTER_NULL_ON_FAILURE,
        ]),
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact channels
    |--------------------------------------------------------------------------
    */

    'contact' => [
        'whatsapp_display' => '(41) 99692-4829',
        'whatsapp_number' => '5541996924829',
        'whatsapp_text' => 'Olá! Vim pelo site do Finestro e gostaria de mais informações.',
        'email' => 'contato@3wonline.com.br',
    ],

    /*
    |--------------------------------------------------------------------------
    | Social profiles (footer icons render only when a URL is set)
    |--------------------------------------------------------------------------
    */

    'social' => [
        'facebook' => env('MARKETING_SOCIAL_FACEBOOK'),
        'instagram' => env('MARKETING_SOCIAL_INSTAGRAM'),
        'linkedin' => env('MARKETING_SOCIAL_LINKEDIN'),
    ],

];
