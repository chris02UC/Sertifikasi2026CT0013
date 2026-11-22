<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Test/seed data for User. Default: an approved tenant.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'nik' => fake()->unique()->numerify('35780###########'),
            'name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::TENANT,
            'status' => UserStatus::APPROVED,
            'verified_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::ADMIN]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => UserStatus::PENDING, 'verified_at' => null]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => UserStatus::REJECTED]);
    }
}
