<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => '+234 80'.fake()->numerify('# ### ####'),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Customer,
            'status' => UserStatus::Active,
            'email_verified_at' => now(),
        ];
    }

    public function staff(UserRole $role = UserRole::Admin): static
    {
        return $this->state(fn () => ['role' => $role]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Suspended]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }
}
