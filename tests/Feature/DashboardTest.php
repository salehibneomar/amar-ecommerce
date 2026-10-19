<?php

use App\Enums\Order\OrderStatusEnum;
use App\Enums\User\PermissionEnum;
use App\Models\Book;
use App\Models\Order;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->superAdmin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('moderators can visit the dashboard', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->get(route('dashboard'))
        ->assertOk();
});

test('the dashboard summarises customers revenue orders and books', function () {
    $this->travelTo('2026-10-15 12:00:00');
    $admin = User::factory()->superAdmin()->create();
    $customer = User::factory()->create(['name' => 'Old Customer', 'created_at' => '2026-09-10 10:00:00']);
    User::factory()->create(['name' => 'New Customer', 'created_at' => '2026-10-02 10:00:00']);
    User::factory()->blocked()->create(['name' => 'Blocked Customer']);
    User::factory()->moderator()->create(['name' => 'Staff Member']);

    Order::factory()->for($customer)->paid()->create(['public_order_id' => 'AE-DONE01', 'total' => 400, 'created_at' => '2026-10-05 10:00:00']);
    Order::factory()->for($customer)->paid()->create(['total' => 200, 'created_at' => '2026-09-05 10:00:00']);
    Order::factory()->for($customer)->create(['total' => 999]);
    Order::factory()->for($customer)->create(['status' => OrderStatusEnum::Cancelled, 'created_at' => '2026-09-20 10:00:00']);

    Book::factory()->create(['created_at' => '2026-09-01 10:00:00']);
    Book::factory()->create();
    Book::factory()->archived()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('stats', fn (array $stats): bool => array_column($stats, 'value') === ['3', '2', '৳400', '3']
            && array_column($stats, 'trend') === ['+2 this month', null, '+100% vs last month', '+2 this month'])
        ->assertViewHas('panels', fn (array $panels): bool => $panels['orders']['pipeline']['total'] === 2
            && $panels['orders']['pipeline']['pending'] === 1
            && array_column($panels['orders']['pipeline']['stages'], 'value') === [1, 0, 1, 0]
            && array_column($panels['inventory'], 'value') === [2, 0, 1])
        ->assertSeeInOrder(['Blocked Customer', 'New Customer', 'Old Customer'])
        ->assertSee(['AE-DONE01', '1 open order'])
        ->assertDontSee('Staff Member');
});

test('moderators only see dashboard sections they have permission for', function () {
    User::factory()->create(['name' => 'Private Customer']);
    Order::factory()->create(['public_order_id' => 'AE-HIDDEN']);
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($moderator)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('stats', [])
        ->assertViewHas('panels', [])
        ->assertDontSee(['Private Customer', 'AE-HIDDEN', __('Revenue this month'), __('Book inventory')]);

    $moderator->givePermissionTo(PermissionEnum::ManageOrders->value);

    $this->actingAs($moderator->refresh())
        ->get(route('dashboard'))
        ->assertViewHas('stats', fn (array $stats): bool => array_column($stats, 'label') === [__('Orders this month')])
        ->assertViewHas('panels', fn (array $panels): bool => array_keys($panels) === ['orders'])
        ->assertSee('AE-HIDDEN')
        ->assertDontSee(['Private Customer', __('Revenue this month'), __('Book inventory')]);
});
