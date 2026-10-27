<?php

use App\Enums\Order\PaymentStatusEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Livewire\Frontend\Cart\Checkout;
use App\Livewire\Frontend\Cart\Counter;
use App\Mail\Order\OrderPlacedMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
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
        'payment.stripe.key' => 'pk_test_key',
        'payment.stripe.secret' => 'sk_test_secret',
        'payment.stripe.webhook_secret' => 'whsec_test',
        'payment.sslcommerz.store_id' => 'teststore',
        'payment.sslcommerz.store_password' => 'teststore@ssl',
        'payment.sslcommerz.api_url' => 'https://sandbox.sslcommerz.com',
        'payment.bkash.app_key' => 'bkash_key',
        'payment.bkash.app_secret' => 'bkash_secret',
        'payment.bkash.username' => 'bkash_user',
        'payment.bkash.password' => 'bkash_pass',
        'payment.bkash.api_url' => 'https://tokenized.sandbox.bka.sh/v1.2.0-beta',
        'app.currency' => 'BDT',
    ]);

    foreach ([PaymentGatewayEnum::Cod, PaymentGatewayEnum::Stripe, PaymentGatewayEnum::SslCommerz, PaymentGatewayEnum::Bkash] as $sortOrder => $gateway) {
        PaymentGateway::factory()->create(['gateway' => $gateway, 'sort_order' => $sortOrder]);
    }
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

function sslCommerzPayment(): Payment
{
    return Payment::factory()->for(Order::factory()->create(['total' => 300]))->create([
        'gateway' => PaymentGatewayEnum::SslCommerz,
        'amount' => 300,
        'currency' => 'BDT',
    ]);
}

/**
 * @param  array<string, mixed>  ...$transactions
 */
function fakeSslCommerzTransactions(Payment $payment, array ...$transactions): void
{
    $responses = Http::sequence();

    foreach ($transactions as $transaction) {
        $responses->push(['APIConnect' => 'DONE', 'no_of_trans_found' => 1, 'element' => [[
            'tran_id' => $payment->reference,
            'currency_type' => 'BDT',
            'currency_amount' => '300.00',
            ...$transaction,
        ]]]);
    }

    Http::fake(['sandbox.sslcommerz.com/validator/*' => $responses]);
}

function bkashPayment(): Payment
{
    return Payment::factory()->for(Order::factory()->create(['total' => 300]))->create([
        'gateway' => PaymentGatewayEnum::Bkash,
        'amount' => 300,
        'currency' => 'BDT',
        'transaction_id' => 'TR0011bkash',
    ]);
}

/**
 * @param  array<string, mixed>  $execute
 */
function fakeBkash(array $execute): void
{
    Http::fake([
        'tokenized.sandbox.bka.sh/*/token/grant' => Http::response(['id_token' => 'bkash-token', 'expires_in' => 3600, 'statusCode' => '0000']),
        'tokenized.sandbox.bka.sh/*/payment/status' => Http::response(['paymentID' => 'TR0011bkash', 'transactionStatus' => 'Initiated', 'statusCode' => '0000']),
        'tokenized.sandbox.bka.sh/*/execute' => Http::response($execute),
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
    PaymentGateway::query()->get()->each->update(['is_enabled' => false]);
    PaymentGateway::factory()->manual()->create();

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

test('sslcommerz checkout places the order and sends the customer to the hosted payment page', function () {
    Http::fake(['sandbox.sslcommerz.com/gwprocess/v4/api.php' => Http::response(['status' => 'SUCCESS', 'GatewayPageURL' => 'https://sandbox.sslcommerz.com/pay/abc'])]);

    $checkout = checkoutWith(PaymentGatewayEnum::SslCommerz->value)->assertHasNoErrors();
    $payment = Payment::query()->sole();
    $checkout->assertRedirect(URL::signedRoute('payments.show', $payment->reference));

    $this->get(URL::signedRoute('payments.show', $payment->reference))->assertRedirect('https://sandbox.sslcommerz.com/pay/abc');

    Http::assertSent(fn (Request $request): bool => $request['tran_id'] === $payment->reference
        && (int) $request['total_amount'] === 300
        && $request['currency'] === 'BDT'
        && $request['store_id'] === 'teststore'
        && $request['success_url'] === route('payments.callback', $payment->reference));
});

test('a failed sslcommerz session sends the customer back to the order', function () {
    Http::fake(['sandbox.sslcommerz.com/gwprocess/v4/api.php' => Http::response(['status' => 'FAILED', 'failedreason' => 'Store Credential Error'])]);
    $payment = sslCommerzPayment();

    $this->get(URL::signedRoute('payments.show', $payment->reference))
        ->assertRedirect(URL::signedRoute('orders.confirmation', $payment->order->public_order_id))
        ->assertSessionHas('error');
});

test('the sslcommerz callback marks the order paid only after the validation api confirms the amount', function () {
    $payment = sslCommerzPayment();
    fakeSslCommerzTransactions($payment, ['status' => 'VALID', 'currency_amount' => '10.00'], ['status' => 'VALID']);

    $this->post(route('payments.callback', $payment->reference), ['tran_id' => $payment->reference, 'status' => 'VALID'])
        ->assertRedirect(route('payments.return', $payment->reference));
    $this->get(route('payments.return', $payment->reference));
    expect($payment->refresh()->status)->toBe(PaymentStatusEnum::Pending);

    $this->get(route('payments.return', $payment->reference))->assertSessionHas('success');

    expect($payment->refresh()->status)->toBe(PaymentStatusEnum::Paid)
        ->and($payment->order->refresh()->payment_status)->toBe(PaymentStatusEnum::Paid);
});

test('an sslcommerz ipn checks the transaction and records a failed payment', function () {
    $payment = sslCommerzPayment();
    fakeSslCommerzTransactions($payment, ['status' => 'FAILED']);

    $this->post(route('payments.webhook', PaymentGatewayEnum::SslCommerz), ['tran_id' => $payment->reference, 'status' => 'VALID'])->assertNoContent();

    expect($payment->refresh()->status)->toBe(PaymentStatusEnum::Failed)
        ->and($payment->order->refresh()->payment_status)->toBe(PaymentStatusEnum::Failed);
});

test('bkash checkout creates a payment and sends the customer to the bkash page', function () {
    Http::fake([
        'tokenized.sandbox.bka.sh/*/token/grant' => Http::response(['id_token' => 'bkash-token', 'expires_in' => 3600, 'statusCode' => '0000']),
        'tokenized.sandbox.bka.sh/*/create' => Http::response(['paymentID' => 'TR0011bkash', 'bkashURL' => 'https://sandbox.payment.bkash.com/?paymentId=TR0011bkash', 'statusCode' => '0000']),
    ]);

    checkoutWith(PaymentGatewayEnum::Bkash->value)->assertHasNoErrors();
    $payment = Payment::query()->sole();

    $this->get(URL::signedRoute('payments.show', $payment->reference))->assertRedirect('https://sandbox.payment.bkash.com/?paymentId=TR0011bkash');

    expect($payment->refresh()->transaction_id)->toBe('TR0011bkash');
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/create')
        && $request->hasHeader('Authorization', 'bkash-token')
        && $request['amount'] === '300'
        && $request['callbackURL'] === route('payments.return', $payment->reference));
});

test('returning from bkash executes the authorized payment and marks the order paid', function () {
    fakeBkash(['paymentID' => 'TR0011bkash', 'trxID' => 'BKX123', 'transactionStatus' => 'Completed', 'amount' => '300', 'currency' => 'BDT', 'statusCode' => '0000']);
    $payment = bkashPayment();

    $this->get(route('payments.return', $payment->reference).'?paymentID=TR0011bkash&status=success')->assertSessionHas('success');

    expect($payment->refresh()->status)->toBe(PaymentStatusEnum::Paid)
        ->and($payment->order->refresh()->payment_status)->toBe(PaymentStatusEnum::Paid);
});

test('a bkash payment the customer did not authorize is marked failed', function () {
    fakeBkash(['statusCode' => '2056', 'statusMessage' => 'Invalid Payment State']);
    $payment = bkashPayment();

    $this->get(route('payments.return', $payment->reference).'?paymentID=TR0011bkash&status=cancel')->assertSessionHas('error');

    expect($payment->refresh()->status)->toBe(PaymentStatusEnum::Failed);
});
