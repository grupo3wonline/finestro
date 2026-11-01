<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CookieConsent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CookieConsent>
 */
class CookieConsentFactory extends Factory
{
    protected $model = CookieConsent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'version' => (string) config('legal.policy_version'),
            'analytics' => fake()->boolean(),
            'marketing' => fake()->boolean(),
            'gpc' => false,
            'source' => CookieConsent::SOURCE_BANNER,
        ];
    }
}
