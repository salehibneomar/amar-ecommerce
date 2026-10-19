<?php

use App\Enums\Order\ContactModeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentMethodEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Enums\User\PermissionEnum;
use App\Livewire\Backoffice\Orders\Index;
use App\Mail\Order\OrderAcknowledgedMail;
use App\Mail\Order\OrderCancelledMail;
use App\Mail\Order\OrderCompletedMail;
use App\Mail\Order\OrderPlacedMail;
use App\Mail\Order\OrderRequestedMail;
use App\Models\Book;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Frontend\Account\BookLibraryService;
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
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($moderator)->get(route('manage.orders.index'))->assertRedirect(route('dashboard'));

    $moderator->givePermissionTo(PermissionEnum::ManageOrders->value);

    $this->actingAs($moderator->refresh())->get(route('manage.orders.index'))->assertOk();
});

test('marking an order as paid with a transaction id completes it and unlocks the books', function () {
    $admin = User::factory()->superAdmin()->create();
    $product = Book::factory()->create();
    $order = orderWith($product);

    Livewire::actingAs($admin)->test(Index::class)
        ->call('viewOrder', $order->id)
        ->set('contactMode', ContactModeEnum::Whatsapp->value)
        ->set('paymentMethod', PaymentMethodEnum::Bkash->value)
        ->set('trxId', 'trx123abc')
        ->set('orderPaymentStatus', PaymentStatusEnum::Paid->value)
        ->assertSet('orderStatus', OrderStatusEnum::Completed->value)
        ->call('save')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->status)->toBe(OrderStatusEnum::Completed)
        ->and($order->transaction_id)->toBe('TRX123ABC')
        ->and($order->contact_mode)->toBe(ContactModeEnum::Whatsapp)
        ->and($order->customer_contact_recorded_at)->not->toBeNull()
        ->and($order->completed_by)->toBe($admin->id)
        ->and($order->has_invoice)->toBeTrue()
        ->and(app(BookLibraryService::class)->ownsProduct($order->user, $product->id))->toBeTrue();
});

test('a paid order needs a transaction id that is not used by another order', function () {
    Order::factory()->paid()->create(['transaction_id' => 'USED123456']);
    $order = orderWith(Book::factory()->create());

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->set('paymentMethod', PaymentMethodEnum::Bkash->value)
        ->set('orderPaymentStatus', PaymentStatusEnum::Paid->value)
        ->call('save')
        ->assertHasErrors(['trxId' => 'required'])
        ->set('trxId', 'USED123456')
        ->call('save')
        ->assertHasErrors(['trxId' => 'unique']);

    expect($order->refresh()->status)->toBe(OrderStatusEnum::Requested);
});

test('an unpaid order cannot be completed', function () {
    $order = orderWith(Book::factory()->create());

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->set('orderStatus', OrderStatusEnum::Completed->value)
        ->call('save')
        ->assertHasErrors('orderStatus');

    expect($order->refresh()->status)->toBe(OrderStatusEnum::Requested);
});

test('completed is disabled until the payment is marked as paid', function () {
    $order = orderWith(Book::factory()->create());
    $enabled = fn (array $options): array => array_keys(array_filter($options, fn (array $option): bool => ! $option['disabled']));

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)->call('viewOrder', $order->id);

    expect(array_keys($component->get('editableStatusOptions')))->toBe([OrderStatusEnum::Requested->value, OrderStatusEnum::Acknowledged->value, OrderStatusEnum::Completed->value])
        ->and($enabled($component->get('editableStatusOptions')))->toBe([OrderStatusEnum::Requested->value, OrderStatusEnum::Acknowledged->value]);

    $component->set('orderPaymentStatus', PaymentStatusEnum::Paid->value)->assertSet('orderStatus', OrderStatusEnum::Completed->value);

    expect($enabled($component->get('editableStatusOptions')))->toBe([OrderStatusEnum::Completed->value]);

    $component->set('orderPaymentStatus', PaymentStatusEnum::Pending->value)->assertSet('orderStatus', OrderStatusEnum::Requested->value);
});

test('order details show items in their own tab', function () {
    $order = orderWith(Book::factory()->create(['name' => 'Tabbed Item Book']));

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewDetails', $order->id)
        ->assertSet('showDetails', true)
        ->assertSee('Tabbed Item Book');
});

test('cancelling an order returns printed copies to stock', function () {
    $product = Book::factory()->physical(3)->create();
    $order = orderWith($product, 2);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewOrder', $order->id)
        ->call('cancelOrder');

    expect($order->refresh()->status)->toBe(OrderStatusEnum::Cancelled)
        ->and($order->payment_status)->toBe(PaymentStatusEnum::NotPaid)
        ->and($product->refresh()->quantity)->toBe(5);
});

test('staff are not notified about order updates they make themselves', function () {
    $admin = User::factory()->superAdmin()->create();
    $other = User::factory()->superAdmin()->create();
    $order = orderWith(Book::factory()->physical(3)->create());

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

test('customers are emailed when their order is acknowledged, completed or cancelled', function () {
    Mail::fake();
    $this->withoutDefer();
    $admin = User::factory()->superAdmin()->create();
    $order = orderWith(Book::factory()->create());
    $email = $order->customer_email;

    Livewire::actingAs($admin)->test(Index::class)
        ->call('viewOrder', $order->id)
        ->set('orderStatus', OrderStatusEnum::Acknowledged->value)
        ->call('save')
        ->call('viewOrder', $order->id)
        ->call('save')
        ->call('viewOrder', $order->id)
        ->set('paymentMethod', PaymentMethodEnum::Bkash->value)
        ->set('trxId', 'PAID123456')
        ->set('orderPaymentStatus', PaymentStatusEnum::Paid->value)
        ->call('save')
        ->assertHasNoErrors();

    Mail::assertSent(OrderAcknowledgedMail::class, 1);
    Mail::assertSent(OrderCompletedMail::class, fn (OrderCompletedMail $mail): bool => $mail->hasTo($email));

    $other = orderWith(Book::factory()->create());

    Livewire::actingAs($admin)->test(Index::class)->call('viewOrder', $other->id)->call('cancelOrder');

    Mail::assertSent(OrderCancelledMail::class, fn (OrderCancelledMail $mail): bool => $mail->hasTo($other->customer_email));
});

test('the email and invoice strike through the original price of a discounted item', function () {
    $order = Order::factory()->paid()->create();
    $order->items()->create(['product_name_snapshot' => 'Discounted Book', 'quantity' => 1, 'unit_price' => 300, 'discount_percentage' => 15, 'line_total' => 255]);
    $order->load(['user', 'items']);

    expect((new OrderCompletedMail($order))->render())->toContain(formatMoney(300).'</s>')->toContain('15% off');

    $invoice = view('pdf.invoice', ['order' => $order, 'currency' => 'BDT', 'invoiceNumber' => 'INV-1', 'logo' => null, 'supportEmail' => null])->render();

    expect($invoice)->toContain('line-through')->toContain('BDT 300')->toContain('15% off');
});

test('every order email renders with the order details', function () {
    $order = orderWith(Book::factory()->create(['name' => 'Mailed Book']))->load(['user', 'items']);

    foreach ([
        new OrderPlacedMail($order),
        new OrderRequestedMail($order),
        new OrderAcknowledgedMail($order),
        new OrderCompletedMail($order),
        new OrderCancelledMail($order),
    ] as $mail) {
        expect($mail->render())->toContain($order->public_order_id)->toContain('Mailed Book');
    }
});
