<?php

use App\Enums\Order\PaymentStatusEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Livewire\Frontend\Cart\Checkout;
use App\Livewire\Frontend\Cart\Counter;
use App\Mail\Order\OrderPlacedMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'payment.enabled' => [PaymentGatewayEnum::Cod->value, PaymentGatewayEnum::Stripe->value],
        'payment.stripe.key' => 'pk_test_key',
        'payment.stripe.secret' => 'sk_test_secret',
        'payment.stripe.webhook_secret' => 'whsec_test',
        'app.currency' => 'BDT',
    ]);
});

function checkoutWith(string $gateway): Testable
{
    $customer = User::factory()->create();

    Livewire::actingAs($customer)->test(Counter::class)->call('add', Product::factory()->create(['price' => 300, 'discount_percentage' => null])->id);

    return Livewire::actingAs($customer)->test(Checkout::class)
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->set('paymentGateway', $gateway)
        ->call('placeOrder');
}

function stripePayment(): Payment
{
    return Payment::factory()->for(Order::factory()->create(['total' => 300]))->create([
        'gateway' => PaymentGatewayEnum::Stripe,
        'amount' => 300,
        'transaction_id' => 'pi_123',
    ]);
}

test('cash on delivery orders open a pending payment and go straight to the confirmation', function () {
    checkoutWith(PaymentGatewayEnum::Cod->value)
        ->assertHasNoErrors()
        ->assertRedirect(URL::signedRoute('orders.confirmation', Order::query()->sole()->public_order_id));

    expect(Payment::query()->sole())
        ->gateway->toBe(PaymentGatewayEnum::Cod)
        ->status->toBe(PaymentStatusEnum::Pending)
        ->amount->toBe(300);
});

test('a manual payment order emails the customer the payment steps', function () {
    Mail::fake();
    $this->withoutDefer();
    config(['payment.enabled' => [PaymentGatewayEnum::Manual->value]]);

    checkoutWith(PaymentGatewayEnum::Manual->value)->assertHasNoErrors();

    expect(Payment::query()->sole()->gateway)->toBe(PaymentGatewayEnum::Manual);
    Mail::assertSent(OrderPlacedMail::class, fn (OrderPlacedMail $mail): bool => $mail->isManualPayment);
});

test('checkout only accepts an enabled payment gateway', function () {
    checkoutWith(PaymentGatewayEnum::Manual->value)->assertHasErrors(['paymentGateway']);

    expect(Order::query()->count())->toBe(0);
});

test('stripe checkout opens the payment page without placing an order', function () {
    Http::fake();

    checkoutWith(PaymentGatewayEnum::Stripe->value)
        ->assertHasNoErrors()
        ->assertRedirect(route('payments.checkout'));

    $this->get(route('payments.checkout'))
        ->assertOk()
        ->assertSee('pk_test_key')
        ->assertSee('30000')
        ->assertSee(__('Test Mode'));

    expect(Order::query()->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0);

    Http::assertNothingSent();
});

test('paying on the stripe page places the order and opens a payment intent for its total', function () {
    Http::fake(['api.stripe.com/v1/payment_intents' => Http::response(['id' => 'pi_123', 'client_secret' => 'pi_123_secret_abc', 'status' => 'requires_payment_method'])]);

    checkoutWith(PaymentGatewayEnum::Stripe->value)->assertHasNoErrors();

    $this->postJson(route('payments.place'))
        ->assertOk()
        ->assertJson(['clientSecret' => 'pi_123_secret_abc']);

    $payment = Payment::query()->sole();

    Http::assertSent(fn (Request $request): bool => $request['amount'] === 30000
        && $request['currency'] === 'bdt'
        && $request['metadata']['reference'] === $payment->reference
        && $request->hasHeader('Idempotency-Key', 'payment-'.$payment->reference));

    expect($payment)
        ->gateway->toBe(PaymentGatewayEnum::Stripe)
        ->transaction_id->toBe('pi_123')
        ->and($payment->order->total)->toBe(300);

    $this->postJson(route('payments.place'))->assertUnprocessable();
    $this->get(route('payments.checkout'))->assertRedirect(route('checkout'));

    expect(Order::query()->count())->toBe(1);
});

test('an unpaid stripe order opens its payment page from a signed link', function () {
    Http::fake(['api.stripe.com/v1/payment_intents' => Http::response(['id' => 'pi_123', 'client_secret' => 'pi_123_secret_abc', 'status' => 'requires_payment_method'])]);
    $payment = stripePayment()->forceFill(['transaction_id' => null]);
    $payment->save();

    $this->get(URL::signedRoute('payments.show', $payment->reference))
        ->assertOk()
        ->assertSee('pi_123_secret_abc')
        ->assertSee(__('Pay Later and View Order'));
});

test('the payment page needs a signed link', function () {
    $this->get(route('payments.show', stripePayment()->reference))->assertForbidden();
});

test('returning from stripe marks the order paid once stripe confirms it', function () {
    Http::fake(['api.stripe.com/v1/payment_intents/pi_123' => Http::response(['id' => 'pi_123', 'status' => 'succeeded'])]);
    $payment = stripePayment();

    $this->get(route('payments.return', $payment->reference))
        ->assertRedirect(URL::signedRoute('orders.confirmation', $payment->order->public_order_id))
        ->assertSessionHas('success');

    expect($payment->refresh()->status)->toBe(PaymentStatusEnum::Paid)
        ->and($payment->paid_at)->not->toBeNull()
        ->and($payment->order->refresh()->payment_status)->toBe(PaymentStatusEnum::Paid);
});

test('a signed stripe webhook updates the payment and a forged one is rejected', function () {
    Http::fake(['api.stripe.com/v1/payment_intents/pi_123' => Http::response(['id' => 'pi_123', 'status' => 'requires_payment_method', 'last_payment_error' => ['message' => 'Declined']])]);
    $payment = stripePayment();
    $body = json_encode(['type' => 'payment_intent.payment_failed', 'data' => ['object' => ['metadata' => ['reference' => $payment->reference]]]]);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$body}", 'whsec_test');
    $send = fn (string $header) => $this->call('POST', route('payments.webhook', PaymentGatewayEnum::Stripe), server: ['HTTP_STRIPE_SIGNATURE' => $header, 'CONTENT_TYPE' => 'application/json'], content: $body);

    $send("t={$timestamp},v1=forged")->assertBadRequest();
    expect($payment->refresh()->status)->toBe(PaymentStatusEnum::Pending);

    $send("t={$timestamp},v1={$signature}")->assertNoContent();
    expect($payment->refresh()->status)->toBe(PaymentStatusEnum::Failed)
        ->and($payment->order->refresh()->payment_status)->toBe(PaymentStatusEnum::Failed);
});
