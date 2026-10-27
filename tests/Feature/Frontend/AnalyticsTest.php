<?php

use App\Livewire\Frontend\Cart\Counter;
use App\Livewire\Frontend\Cart\Index;
use App\Models\AnalyticsTracker;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

test('the storefront loads no tracking script while no tracker is set up', function () {
    $this->get(route('home'))->assertOk()->assertDontSee('window.trackAnalytics', false);
});

test('the storefront loads only the enabled trackers', function () {
    AnalyticsTracker::factory()->googleTagManager()->create(['tracking_id' => 'GTM-ABC1234']);
    AnalyticsTracker::factory()->metaPixel()->create(['tracking_id' => '123456789012345']);
    AnalyticsTracker::factory()->disabled()->create(['tracking_id' => 'G-ABC123XYZ9']);

    $this->get(route('home'))
        ->assertSee('googletagmanager.com/gtm.js?id=GTM-ABC1234', false)
        ->assertSee('connect.facebook.net/en_US/fbevents.js', false)
        ->assertDontSee('gtag/js?id=G-ABC123XYZ9', false);
});

test('the admin panel is never tracked', function () {
    AnalyticsTracker::factory()->create();

    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('window.trackAnalytics', false);
});

test('the product page sends a view item event with the final price', function () {
    AnalyticsTracker::factory()->create();
    $product = Product::factory()->create(['sku' => 'SKU-VIEW-1', 'price' => 1000, 'discount_percentage' => 10]);

    $this->get(route('shop.product', $product->slug))
        ->assertOk()
        ->assertSee('window.trackAnalytics?.(', false)
        ->assertViewHas('analyticsEvent', fn (array $event): bool => $event['name'] === 'view_item'
            && $event['params']['value'] === 900
            && $event['params']['items'][0]['item_id'] === 'SKU-VIEW-1'
            && $event['params']['items'][0]['discount'] === 100);
});

test('cart changes send add and remove events while a tracker is on', function () {
    AnalyticsTracker::factory()->create();
    $product = Product::factory()->create(['quantity' => 10]);

    Livewire::test(Counter::class)
        ->call('add', $product->id)
        ->assertDispatched('analytics-event', fn (string $event, array $params): bool => $params['name'] === 'add_to_cart' && $params['params']['items'][0]['quantity'] === 1);

    Livewire::test(Index::class)
        ->call('updateQuantity', $product->id, 3)
        ->assertDispatched('analytics-event', fn (string $event, array $params): bool => $params['name'] === 'add_to_cart' && $params['params']['items'][0]['quantity'] === 2)
        ->call('remove', $product->id)
        ->assertDispatched('analytics-event', fn (string $event, array $params): bool => $params['name'] === 'remove_from_cart' && $params['params']['items'][0]['quantity'] === 3);
});

test('cart changes send no events while every tracker is off', function () {
    AnalyticsTracker::factory()->disabled()->create();
    $product = Product::factory()->create();

    Livewire::test(Counter::class)
        ->call('add', $product->id)
        ->assertNotDispatched('analytics-event');
});

test('a purchase is sent once per order even when the confirmation page reloads', function () {
    AnalyticsTracker::factory()->create();
    $order = Order::factory()->create();
    $url = URL::signedRoute('orders.confirmation', $order->public_order_id);

    $this->get($url)->assertOk()->assertViewHas('analyticsEvent', fn (?array $event): bool => $event['name'] === 'purchase'
        && $event['params']['transaction_id'] === $order->public_order_id
        && $event['params']['value'] === $order->total);

    $this->get($url)->assertOk()->assertViewHas('analyticsEvent', null);
});
