<?php

use App\Enums\User\PermissionEnum;
use App\Livewire\Backoffice\Offers\Index as OfferIndex;
use App\Livewire\Backoffice\Orders\Index as OrderIndex;
use App\Livewire\Frontend\Notifications\Dropdown;
use App\Livewire\Frontend\Notifications\Index;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

test('customers are notified when their order is updated', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create();

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(OrderIndex::class)
        ->call('viewOrder', $order->id)
        ->call('cancelOrder');

    $notification = $customer->notifications()->sole();

    Livewire::actingAs($customer)->test(Dropdown::class)
        ->assertSee($order->public_order_id)
        ->call('openNotification', $notification->id)
        ->assertRedirect(route('user.orders.detail', $order->public_order_id));

    expect($notification->refresh()->read_at)->not->toBeNull();
});

test('publishing an active global offer notifies active customers only', function () {
    $customer = User::factory()->create();
    $blocked = User::factory()->blocked()->create();
    $staff = User::factory()->moderator()->create();
    $staff->givePermissionTo(PermissionEnum::ManageOffers->value);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(OfferIndex::class)
        ->call('createOffer')
        ->set('name', 'Eid Sale')
        ->set('headline', 'Products on sale')
        ->set('discountPercentage', '20')
        ->set('isActive', true)
        ->call('saveOffer')
        ->assertHasNoErrors();

    expect($customer->notifications()->count())->toBe(1)
        ->and($blocked->notifications()->count())->toBe(0)
        ->and($staff->notifications()->count())->toBe(0);

    $this->actingAs($customer)->get(route('user.notifications'))
        ->assertOk()
        ->assertSee('Eid Sale');
});

test('customers mark notifications read and unread from their list', function () {
    $customer = User::factory()->create();
    $orders = Order::factory()->for($customer)->count(2)->create();
    $admin = User::factory()->superAdmin()->create();

    foreach ($orders as $order) {
        Livewire::actingAs($admin)->test(OrderIndex::class)->call('viewOrder', $order->id)->call('cancelOrder');
    }

    $notification = $customer->notifications()->first();

    $component = Livewire::actingAs($customer)->test(Index::class)
        ->call('toggleNotification', $notification->id);

    expect($notification->refresh()->read_at)->not->toBeNull();

    $component->call('markAllNotifications', true);
    expect($customer->unreadNotifications()->count())->toBe(0);

    $component->call('markAllNotifications', false);
    expect($customer->unreadNotifications()->count())->toBe(2);
});

test('a customer cannot touch another user\'s notification', function () {
    $owner = User::factory()->create();
    $order = Order::factory()->for($owner)->create();

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(OrderIndex::class)->call('viewOrder', $order->id)->call('cancelOrder');

    Livewire::actingAs(User::factory()->create())->test(Index::class)
        ->call('toggleNotification', $owner->notifications()->sole()->id)
        ->assertNotFound();
});
