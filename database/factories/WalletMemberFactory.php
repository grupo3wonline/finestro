<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletMember>
 */
class WalletMemberFactory extends Factory
{
    protected $model = WalletMember::class;

    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'user_id' => User::factory(),
            'email' => null,
            'role' => fake()->randomElement(['viewer', 'editor', 'admin']),
            'accepted_at' => now(),
        ];
    }

    /**
     * Membership still pending acceptance.
     */
    public function pending(): static
    {
        return $this->state(fn (): array => ['accepted_at' => null]);
    }

    /**
     * Membership with a specific role.
     */
    public function role(string $role): static
    {
        return $this->state(fn (): array => ['role' => $role]);
    }

    /**
     * Unclaimed, email-based invite for someone without an account yet.
     * Mirrors the pending-invite flow supported by the wallet_members schema
     * and WalletMember::scopeClaimableForEmail().
     */
    public function unclaimed(): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'email' => fake()->safeEmail(),
            'invitation_token' => bin2hex(random_bytes(32)),
            'accepted_at' => null,
            'expires_at' => now()->addDays(14),
        ]);
    }
}
