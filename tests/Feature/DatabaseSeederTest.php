<?php

use App\Enums\User\UserRoleEnum;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\NotificationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Notifications\DatabaseNotification;
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

test('the notification seeder fills the inbox with read and unread notifications', function () {
    $this->seed(RoleSeeder::class);
    User::factory()->superAdmin()->create();
    Order::factory(2)->for(User::factory())->create();

    $this->seed(NotificationSeeder::class);

    expect(DatabaseNotification::count())->toBeGreaterThan(0)
        ->and(DatabaseNotification::whereNotNull('read_at')->exists())->toBeTrue()
        ->and(DatabaseNotification::whereNull('read_at')->exists())->toBeTrue();
});
