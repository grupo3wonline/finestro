<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'user',
            'terms_accepted_at' => now(),
            'terms_version' => config('legal.policy_version'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Assign the ACL role that matches the legacy `role` column.
     *
     * The `role` column is guarded against mass assignment on the model, but
     * the factory builds instances via Model::unguarded(), so the value from
     * definition()/states() is persisted and keeps legacy-user fixtures
     * working (e.g. the roles-seeder migration test). Authorization itself is
     * driven by the Spatie role assigned here.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            if ($user->roles()->exists()) {
                return;
            }

            $user->syncRoles([$user->role === 'admin' ? 'admin' : 'user']);
        });
    }

    /**
     * Indicate that the user is an administrator.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => 'admin',
        ]);
    }

    /**
     * Indicate that the user has not accepted the Terms of Use yet.
     */
    public function withoutTermsAccepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'terms_accepted_at' => null,
            'terms_version' => null,
        ]);
    }

    /**
     * Indicate that the account is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'suspended_at' => now(),
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
