<?php

use App\Enums\User\UserRoleEnum;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

test('turning seeding off only seeds the roles', function () {
    config(['database.seeding_enabled' => false, 'database.seed_admin' => ['email' => null, 'password' => null]]);
    Role::query()->delete();

    $this->seed(DatabaseSeeder::class);

    expect(Role::query()->pluck('name')->sort()->values()->all())->toBe(['admin', 'editor', 'moderator', 'super_admin', 'user'])
        ->and(Role::findByName('moderator')->permissions->pluck('name')->sort()->values()->all())->toBe(['manage_categories', 'manage_orders', 'manage_products'])
        ->and(Role::findByName('editor')->permissions->pluck('name')->all())->toBe(['manage_faqs'])
        ->and(User::count())->toBe(0)
        ->and(Product::count())->toBe(0);
});

test('turning seeding off seeds one super admin from the env once', function () {
    config(['database.seeding_enabled' => false, 'database.seed_admin' => ['email' => 'owner@example.com', 'password' => 'a-strong-password']]);

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $admin = User::sole();

    expect($admin->email)->toBe('owner@example.com')
        ->and($admin->hasRole(UserRoleEnum::SuperAdmin->value))->toBeTrue()
        ->and(Hash::check('a-strong-password', $admin->password))->toBeTrue()
        ->and(Product::count())->toBe(0);
});
