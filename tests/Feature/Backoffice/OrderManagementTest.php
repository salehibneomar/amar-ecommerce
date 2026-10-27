<?php

use App\Enums\Order\ContactModeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentMethodEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Enums\User\PermissionEnum;
use App\Livewire\Backoffice\Orders\Index;
use App\Mail\Order\OrderCancelledMail;
use App\Mail\Order\OrderCompletedMail;
use App\Mail\Order\OrderPlacedMail;
use App\Mail\Order\OrderProcessingMail;
use App\Mail\Order\OrderRequestedMail;
use App\Mail\Order\OrderShippedMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function orderWith(Product $product, int $quantity = 1): Order
{
    $order = Order::factory()->create();
    $order->items()->create([
        'product_id' => $product->id,
        'product_name_snapshot' => $product->name,
        'quantity' => $quantity,
        'unit_price' => $product->price,
        'line_total' => $product->price,
    ]);

    return $order;
}

test('regular users are redirected from order management', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('manage.orders.index'))
        ->assertRedirect(route('user.dashboard'));
});

test('moderators need the manage orders permission', function () {
    $moderator = User::factory()->editor()->create();

    $this->actingAs($moderator)->get(route('manage.orders.index'))->assertRedirect(route('dashboard'));

    $moderator->givePermissionTo(PermissionEnum::ManageOrders->value);

    $this->actingAs($moderator->refresh())->get(route('manage.orders.index'))->assertOk();
});

test('without a payment process staff mark the payment by hand and no payment record is kept', function () {
    $order = orderWith(Product::factory()->create());

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->assertDontSee(__('TrxID'))
        ->set('orderPaymentStatus', PaymentStatusEnum::Paid->value)
        ->assertSet('orderStatus', OrderStatusEnum::Processing->value)
        ->call('save')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->status)->toBe(OrderStatusEnum::Processing)
        ->and($order->payment_status)->toBe(PaymentStatusEnum::Paid)
        ->and($order->has_invoice)->toBeTrue()
        ->and(Payment::query()->count())->toBe(0);
});

test('with the manual process a paid order records the method and transaction id on its payment', function () {
    PaymentGateway::factory()->manual()->create();
    $admin = User::factory()->superAdmin()->create();
    $order = orderWith(Product::factory()->create());
    Payment::factory()->for($order)->create();

    Livewire::actingAs($admin)->test(Index::class)
        ->call('viewOrder', $order->id)
        ->assertSee(__('TrxID'))
        ->set('contactMode', ContactModeEnum::Whatsapp->value)
        ->set('paymentMethod', PaymentMethodEnum::Bkash->value)
        ->set('trxId', 'trx123abc')
        ->set('orderPaymentStatus', PaymentStatusEnum::Paid->value)
        ->call('save')
        ->assertHasNoErrors();

    $order->refresh()->load('payment');

    expect($order->status)->toBe(OrderStatusEnum::Processing)
        ->and($order->contact_mode)->toBe(ContactModeEnum::Whatsapp)
        ->and($order->customer_contact_recorded_at)->not->toBeNull()
        ->and($order->payment->gateway)->toBe(PaymentGatewayEnum::Manual)
        ->and($order->payment->method)->toBe(PaymentMethodEnum::Bkash)
        ->and($order->payment->transaction_id)->toBe('TRX123ABC')
        ->and($order->payment->status)->toBe(PaymentStatusEnum::Paid)
        ->and($order->payment->amount)->toBe($order->total)
        ->and($order->payment->paid_at)->not->toBeNull();
});

test('a manual payment needs a transaction id that is not used by another order', function () {
    PaymentGateway::factory()->manual()->create();
    Payment::factory()->paid()->create(['transaction_id' => 'USED123456']);
    $order = orderWith(Product::factory()->create());
    Payment::factory()->for($order)->create();

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->set('paymentMethod', PaymentMethodEnum::Bkash->value)
        ->set('orderPaymentStatus', PaymentStatusEnum::Paid->value)
        ->call('save')
        ->assertHasErrors(['trxId' => 'required'])
        ->set('trxId', 'USED123456')
        ->call('save')
        ->assertHasErrors(['trxId' => 'unique']);

    expect($order->refresh()->status)->toBe(OrderStatusEnum::Pending);
});

test('an unpaid order cannot be completed', function () {
    $order = orderWith(Product::factory()->create());

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->set('orderStatus', OrderStatusEnum::Completed->value)
        ->call('save')
        ->assertHasErrors('orderStatus');

    expect($order->refresh()->status)->toBe(OrderStatusEnum::Pending);
});

test('completed is disabled until the payment is marked as paid', function () {
    $order = orderWith(Product::factory()->create());
    $enabled = fn (array $options): array => array_keys(array_filter($options, fn (array $option): bool => ! $option['disabled']));

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)->call('viewOrder', $order->id);

    expect(array_keys($component->get('editableStatusOptions')))->toBe([OrderStatusEnum::Pending->value, OrderStatusEnum::Processing->value, OrderStatusEnum::Shipped->value, OrderStatusEnum::Completed->value])
        ->and($enabled($component->get('editableStatusOptions')))->toBe([OrderStatusEnum::Pending->value, OrderStatusEnum::Processing->value, OrderStatusEnum::Shipped->value]);

    $component->set('orderPaymentStatus', PaymentStatusEnum::Paid->value)->set('orderStatus', OrderStatusEnum::Completed->value);

    expect($enabled($component->get('editableStatusOptions')))->toContain(OrderStatusEnum::Completed->value);

    $component->set('orderPaymentStatus', PaymentStatusEnum::Pending->value)->assertSet('orderStatus', OrderStatusEnum::Shipped->value);
});

test('only a paid order can be refunded', function () {
    $order = orderWith(Product::factory()->create());

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->set('orderPaymentStatus', PaymentStatusEnum::Refunded->value)
        ->call('save')
        ->assertHasErrors('orderPaymentStatus');

    expect($order->refresh()->payment_status)->toBe(PaymentStatusEnum::Pending);
});

test('order details show items in their own tab', function () {
    $order = orderWith(Product::factory()->create(['name' => 'Tabbed Item Product']));

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewDetails', $order->id)
        ->assertSet('showDetails', true)
        ->assertSee('Tabbed Item Product');
});

test('cancelling an order returns its items to stock and cancels the payment', function () {
    PaymentGateway::factory()->manual()->create();
    $product = Product::factory()->stock(3)->create();
    $order = orderWith($product, 2);
    Payment::factory()->for($order)->create();

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->call('cancelOrder');

    expect($order->refresh()->status)->toBe(OrderStatusEnum::Cancelled)
        ->and($order->payment_status)->toBe(PaymentStatusEnum::Cancelled)
        ->and($order->payment->status)->toBe(PaymentStatusEnum::Cancelled)
        ->and($product->refresh()->quantity)->toBe(5);
});

test('an order stays open when its card payment cannot be cancelled', function () {
    config(['payment.stripe.key' => 'pk_test_key', 'payment.stripe.secret' => 'sk_test_secret']);
    PaymentGateway::factory()->create(['gateway' => PaymentGatewayEnum::Stripe]);
    Http::fake(['*/payment_intents/pi_123/cancel' => Http::response(['error' => ['message' => 'Down']], 500)]);
    $product = Product::factory()->stock(3)->create();
    $order = orderWith($product, 2);
    Payment::factory()->for($order)->create(['gateway' => PaymentGatewayEnum::Stripe, 'transaction_id' => 'pi_123']);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->call('cancelOrder')
        ->assertHasErrors('order');

    expect($order->refresh()->status)->toBe(OrderStatusEnum::Pending)
        ->and($order->payment->status)->toBe(PaymentStatusEnum::Pending)
        ->and($product->refresh()->quantity)->toBe(3);
});

test('card payments show the gateway with the method', function () {
    $order = Order::factory()->create();
    Payment::factory()->for($order)->create(['gateway' => PaymentGatewayEnum::Stripe]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewDetails', $order->id)
        ->assertSee('Stripe')
        ->assertDontSee('Stripe / Stripe');
});

test('a paid order must be refunded before it is cancelled', function () {
    $order = Order::factory()->create(['status' => OrderStatusEnum::Processing, 'payment_status' => PaymentStatusEnum::Paid]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->call('cancelOrder')
        ->assertHasErrors('order');

    expect($order->refresh()->status)->toBe(OrderStatusEnum::Processing);
});

test('staff are not notified about order updates they make themselves', function () {
    $admin = User::factory()->superAdmin()->create();
    $other = User::factory()->superAdmin()->create();
    $order = orderWith(Product::factory()->stock(3)->create());

    Livewire::actingAs($admin)->test(Index::class)
        ->call('viewOrder', $order->id)
        ->call('cancelOrder');

    expect($admin->notifications()->count())->toBe(0)
        ->and($other->notifications()->count())->toBe(1);
});

test('completed orders cannot be cancelled', function () {
    $order = Order::factory()->paid()->create();

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->call('cancelOrder')
        ->assertHasErrors('order');

    expect($order->refresh()->status)->toBe(OrderStatusEnum::Completed);
});

test('customers are emailed when their order is processing, shipped, completed or cancelled', function () {
    Mail::fake();
    $this->withoutDefer();
    $admin = User::factory()->superAdmin()->create();
    $order = orderWith(Product::factory()->create());
    $email = $order->customer_email;

    Livewire::actingAs($admin)->test(Index::class)
        ->call('viewOrder', $order->id)
        ->set('orderStatus', OrderStatusEnum::Processing->value)
        ->call('save')
        ->call('viewOrder', $order->id)
        ->call('save')
        ->call('viewOrder', $order->id)
        ->set('orderStatus', OrderStatusEnum::Shipped->value)
        ->call('save')
        ->call('viewOrder', $order->id)
        ->set('orderPaymentStatus', PaymentStatusEnum::Paid->value)
        ->set('orderStatus', OrderStatusEnum::Completed->value)
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertSent(OrderProcessingMail::class, 1);
    Mail::assertSent(OrderShippedMail::class, 1);
    Mail::assertSent(OrderCompletedMail::class, fn (OrderCompletedMail $mail): bool => $mail->hasTo($email));

    $other = orderWith(Product::factory()->create());

    Livewire::actingAs($admin)->test(Index::class)->call('viewOrder', $other->id)->call('cancelOrder');

    Mail::assertSent(OrderCancelledMail::class, fn (OrderCancelledMail $mail): bool => $mail->hasTo($other->customer_email));
});

test('the email and invoice strike through the original price of a discounted item', function () {
    $order = Order::factory()->paid()->create();
    $order->items()->create(['product_name_snapshot' => 'Discounted Product', 'quantity' => 1, 'unit_price' => 300, 'discount_percentage' => 15, 'line_total' => 255]);
    $order->load(['user', 'items']);

    expect((new OrderCompletedMail($order))->render())->toContain(formatMoney(300).'</s>')->toContain('15% off');

    $invoice = view('pdf.invoice', ['order' => $order, 'currency' => 'BDT', 'invoiceNumber' => 'INV-1', 'logo' => null, 'supportEmail' => null])->render();

    expect($invoice)->toContain('line-through')->toContain('BDT 300')->toContain('15% off');
});

test('every order email renders with the order details', function () {
    $order = orderWith(Product::factory()->create(['name' => 'Mailed Product']))->load(['user', 'items']);

    foreach ([
        new OrderPlacedMail($order),
        new OrderPlacedMail($order, isManualPayment: true),
        new OrderRequestedMail($order),
        new OrderProcessingMail($order),
        new OrderShippedMail($order),
        new OrderCompletedMail($order),
        new OrderCancelledMail($order),
    ] as $mail) {
        expect($mail->render())->toContain($order->public_order_id)->toContain('Mailed Product');
    }
});
