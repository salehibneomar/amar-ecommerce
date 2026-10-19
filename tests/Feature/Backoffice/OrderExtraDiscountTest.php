<?php

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentMethodEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Livewire\Backoffice\Orders\Index;
use App\Models\Order;
use App\Models\User;
use App\Services\Backoffice\Sales\SalesReportService;
use Livewire\Livewire;

beforeEach(function () {
    config(['shop.max_extra_discount' => 50]);
});

test('an extra discount is applied on the total after the offer discount when the order is saved', function () {
    $order = Order::factory()->create(['subtotal' => 1000, 'discount_amount' => 105, 'total' => 895]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->set('extraDiscountPercentage', '15')
        ->assertSet('extraDiscountPreview', ['percentage' => 15, 'amount' => 134, 'total' => 761])
        ->assertSeeHtml('max="50"')
        ->call('save')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->extra_discount_percentage)->toBe(15)
        ->and($order->extra_discount_amount)->toBe(134)
        ->and($order->total)->toBe(761);
});

test('saving a new extra discount replaces the old one and zero removes it', function () {
    $order = Order::factory()->create(['subtotal' => 1000, 'total' => 900, 'extra_discount_percentage' => 10, 'extra_discount_amount' => 100]);
    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class);

    $component->call('viewOrder', $order->id)->assertSet('extraDiscountPercentage', '10')->set('extraDiscountPercentage', '20')->call('save');

    expect($order->refresh()->total)->toBe(800)->and($order->extra_discount_amount)->toBe(200);

    $component->call('viewOrder', $order->id)->set('extraDiscountPercentage', '0')->call('save');

    expect($order->refresh()->total)->toBe(1000)->and($order->extra_discount_amount)->toBe(0);
});

test('the extra discount must be a whole percentage within the configured limit', function (string $percentage) {
    $order = Order::factory()->create(['subtotal' => 1000, 'total' => 1000]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->set('extraDiscountPercentage', $percentage)
        ->assertHasNoErrors()
        ->assertSet('extraDiscountPreview', ['percentage' => 0, 'amount' => 0, 'total' => 1000])
        ->call('save')
        ->assertHasErrors('extraDiscountPercentage');

    expect($order->refresh()->total)->toBe(1000);
})->with(['over the limit' => '51', 'decimal' => '12.5', 'negative' => '-5']);

test('an empty extra discount saves as no discount', function () {
    $order = Order::factory()->create(['subtotal' => 1000, 'total' => 900, 'extra_discount_percentage' => 10, 'extra_discount_amount' => 100]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->set('extraDiscountPercentage', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($order->refresh()->total)->toBe(1000)->and($order->extra_discount_percentage)->toBe(0);
});

test('an extra discount can be added in the same save that marks a requested or acknowledged order as paid', function (OrderStatusEnum $status) {
    $order = Order::factory()->create(['subtotal' => 255, 'total' => 255, 'status' => $status]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->set('extraDiscountPercentage', '5')
        ->set('paymentMethod', PaymentMethodEnum::Bkash->value)
        ->set('trxId', 'ZANGTRXD')
        ->set('orderPaymentStatus', PaymentStatusEnum::Paid->value)
        ->call('save')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->status)->toBe(OrderStatusEnum::Completed)
        ->and($order->extra_discount_amount)->toBe(13)
        ->and($order->total)->toBe(242);
})->with([OrderStatusEnum::Requested, OrderStatusEnum::Acknowledged]);

test('the extra discount is locked once the order is paid', function () {
    $order = Order::factory()->paid()->create(['subtotal' => 1000, 'total' => 900, 'extra_discount_percentage' => 10, 'extra_discount_amount' => 100]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->call('save')
        ->assertHasNoErrors()
        ->call('viewOrder', $order->id)
        ->set('extraDiscountPercentage', '20')
        ->assertSet('extraDiscountPreview', ['percentage' => 10, 'amount' => 100, 'total' => 900])
        ->call('save')
        ->assertHasErrors('extraDiscountPercentage');

    expect($order->refresh()->total)->toBe(900)->and($order->extra_discount_percentage)->toBe(10);
});

test('the extra discount counts towards the sales report discount', function () {
    Order::factory()->paid()->create(['subtotal' => 1000, 'discount_amount' => 100, 'extra_discount_percentage' => 10, 'extra_discount_amount' => 90, 'total' => 810]);

    $summary = app(SalesReportService::class)->getSummary(now()->month, now()->year);

    expect($summary['discount'])->toBe(190)->and($summary['revenue'])->toBe(810);
});

test('the customer order page only mentions an extra discount when there is one', function () {
    $user = User::factory()->create();
    $plain = Order::factory()->paid()->for($user)->create();
    $discounted = Order::factory()->paid()->for($user)->create(['subtotal' => 1000, 'total' => 750, 'extra_discount_percentage' => 25, 'extra_discount_amount' => 250]);

    $this->actingAs($user)->get(route('user.orders.detail', $plain))->assertOk()->assertDontSee('Extra discount');
    $this->actingAs($user)->get(route('user.orders.detail', $discounted))->assertOk()->assertSee('Extra discount (25%)');
});

test('staff without the manage orders permission cannot reach the extra discount', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->get(route('manage.orders.index'))
        ->assertRedirect(route('dashboard'));
});

test('a cancelled order keeps its total when saved with an extra discount', function () {
    $order = Order::factory()->create(['subtotal' => 1000, 'total' => 1000, 'status' => OrderStatusEnum::Cancelled, 'payment_status' => PaymentStatusEnum::NotPaid]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->set('orderStatus', OrderStatusEnum::Requested->value)
        ->set('extraDiscountPercentage', '10')
        ->call('save')
        ->assertHasErrors('orderStatus');

    expect($order->refresh()->total)->toBe(1000);
});
