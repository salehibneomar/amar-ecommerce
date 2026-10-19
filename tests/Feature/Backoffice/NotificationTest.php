<?php

use App\Enums\Notification\NotificationReadStatusEnum;
use App\Enums\User\PermissionEnum;
use App\Livewire\Backoffice\Notifications\Dropdown;
use App\Livewire\Backoffice\Notifications\Index;
use App\Livewire\Backoffice\Orders\Index as OrderIndex;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

function orderModerator(): User
{
    $moderator = User::factory()->moderator()->create();
    $moderator->givePermissionTo(PermissionEnum::ManageOrders->value);

    return $moderator;
}

function cancelOrderAs(User $actor, Order $order): void
{
    Livewire::actingAs($actor)->test(OrderIndex::class)
        ->call('viewOrder', $order->id)
        ->call('cancelOrder');
}

test('order changes notify admins and order moderators except the actor', function () {
    $actor = orderModerator();
    $admin = User::factory()->superAdmin()->create();
    $orderModerator = orderModerator();
    $otherModerator = User::factory()->moderator()->create();
    $order = Order::factory()->create();

    cancelOrderAs($actor, $order);

    expect($admin->notifications()->count())->toBe(1)
        ->and($orderModerator->notifications()->count())->toBe(1)
        ->and($otherModerator->notifications()->count())->toBe(0)
        ->and($actor->notifications()->count())->toBe(0)
        ->and($order->refresh()->updated_by)->toBe($actor->id);
});

test('the dropdown shows the latest five and opening one marks it read', function () {
    $admin = User::factory()->superAdmin()->create();
    $orders = Order::factory()->count(6)->create();
    $actor = orderModerator();

    foreach ($orders as $index => $order) {
        $this->travelTo(now()->addMinutes($index));
        cancelOrderAs($actor, $order);
    }

    $latest = $admin->notifications()->latest()->first();

    $component = Livewire::actingAs($admin)->test(Dropdown::class)
        ->assertSee(__('View all notifications'))
        ->assertSee($orders->last()->public_order_id)
        ->assertDontSee($orders->first()->public_order_id);

    expect($component->get('unreadCount'))->toBe(6);

    $component->call('openNotification', $latest->id)
        ->assertRedirect(route('manage.orders.index', ['order' => $orders->last()->id]));

    expect($latest->refresh()->read_at)->not->toBeNull();
});

test('staff manage their notifications from the table', function () {
    $admin = User::factory()->superAdmin()->create();
    $actor = orderModerator();
    $first = Order::factory()->create();
    $second = Order::factory()->create();

    cancelOrderAs($actor, $first);
    $this->travel(1)->minutes();
    cancelOrderAs($actor, $second);

    $notification = $admin->notifications()->latest()->first();

    $this->actingAs($admin)->get(route('manage.notifications.index'))
        ->assertOk()
        ->assertSeeInOrder([$second->public_order_id, $first->public_order_id]);

    $component = Livewire::actingAs($admin)->test(Index::class)
        ->call('toggleNotification', $notification->id);

    expect($notification->refresh()->read_at)->not->toBeNull();

    $component->call('toggleNotification', $notification->id);
    expect($notification->refresh()->read_at)->toBeNull();

    $component->call('markAllNotifications', true);
    expect($admin->unreadNotifications()->count())->toBe(0);

    $component->call('markAllNotifications', false);
    expect($admin->unreadNotifications()->count())->toBe(2);
});

test('staff search and filter notifications by read status', function () {
    $admin = User::factory()->superAdmin()->create();
    $actor = orderModerator();
    $first = Order::factory()->create();
    $second = Order::factory()->create();

    cancelOrderAs($actor, $first);
    cancelOrderAs($actor, $second);
    $admin->notifications()->where('data', 'like', '%'.$first->public_order_id.'%')->sole()->markAsRead();

    Livewire::actingAs($admin)->test(Index::class)
        ->set('status', NotificationReadStatusEnum::Unread->value)
        ->assertSee($second->public_order_id)
        ->assertDontSee($first->public_order_id)
        ->set('status', NotificationReadStatusEnum::Read->value)
        ->assertSee($first->public_order_id)
        ->assertDontSee($second->public_order_id)
        ->set('status', '')
        ->set('search', $second->public_order_id)
        ->assertSee($second->public_order_id)
        ->assertDontSee($first->public_order_id);
});

test('customers cannot open the staff notifications page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('manage.notifications.index'))
        ->assertRedirect(route('user.dashboard'));
});
