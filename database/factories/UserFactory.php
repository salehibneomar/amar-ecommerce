<?php

namespace Database\Factories;

use App\Enums\User\PermissionEnum;
use App\Enums\User\UserRoleEnum;
use App\Enums\User\UserStatusEnum;
use App\Models\Role;
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

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            Role::findOrCreate(UserRoleEnum::User->value);
            $user->assignRole(UserRoleEnum::User->value);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('123456'),
            'remember_token' => Str::random(10),
            'status' => UserStatusEnum::Active,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->afterCreating(function (User $user): void {
            Role::findOrCreate(UserRoleEnum::SuperAdmin->value);
            $user->syncRoles(UserRoleEnum::SuperAdmin->value);
        });
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user): void {
            Role::findOrCreate(UserRoleEnum::Admin->value);
            $user->syncRoles(UserRoleEnum::Admin->value);
            $user->syncPermissions(array_column(PermissionEnum::cases(), 'value'));
        });
    }

    public function moderator(): static
    {
        return $this->afterCreating(function (User $user): void {
            Role::findOrCreate(UserRoleEnum::Moderator->value);
            $user->syncRoles(UserRoleEnum::Moderator->value);
        });
    }

    public function editor(): static
    {
        return $this->afterCreating(function (User $user): void {
            Role::findOrCreate(UserRoleEnum::Editor->value);
            $user->syncRoles(UserRoleEnum::Editor->value);
        });
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatusEnum::Blocked,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this;
    }
}
