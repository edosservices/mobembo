<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '+2438'.fake()->unique()->numerify('########'),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::User,
            'status' => AccountStatus::Active,
            'kyc_status' => KycStatus::NotSubmitted,
            'referral_code' => 'ZLV'.fake()->unique()->numerify('#####'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Admin,
        ]);
    }
}
