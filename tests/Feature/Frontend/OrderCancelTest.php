<?php

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Mail\Order\OrderCancelledMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('customers can cancel their own pending order and its stock comes back', function () {
    Mail::fake();
    $customer = User::factory()->create();
    $product = Product::factory()->stock(3)->create();
    $order = Order::factory()->for($customer)->create();
    $order->items()->create(['product_id' => $product->id, 'product_name_snapshot' => $product->name, 'quantity' => 2, 'unit_price' => $product->price, 'line_total' => $product->price * 2]);

    $this->actingAs($customer)->get(route('user.orders.detail', $order->public_order_id))->assertSee(__('Cancel Order'));
    $this->actingAs($customer)->post(route('user.orders.cancel', $order->public_order_id))->assertSessionHas('success');

    expect($order->refresh()->status)->toBe(OrderStatusEnum::Cancelled)
        ->and($order->payment_status)->toBe(PaymentStatusEnum::Cancelled)
        ->and($product->refresh()->quantity)->toBe(5);
    Mail::assertSent(OrderCancelledMail::class);
});

test('customers cannot cancel an order that is no longer pending', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create(['status' => OrderStatusEnum::Processing]);

    $this->actingAs($customer)->get(route('user.orders.detail', $order->public_order_id))->assertDontSee(__('Cancel Order'));
    $this->actingAs($customer)->post(route('user.orders.cancel', $order->public_order_id))->assertSessionHas('error');

    expect($order->refresh()->status)->toBe(OrderStatusEnum::Processing);
});

test('customers cannot cancel another customer\'s order', function () {
    $order = Order::factory()->create();

    $this->actingAs(User::factory()->create())->post(route('user.orders.cancel', $order->public_order_id))->assertNotFound();

    expect($order->refresh()->status)->toBe(OrderStatusEnum::Pending);
});
