<?php

use App\Enums\Content\OfferTypeEnum;
use App\Enums\Notification\NotificationTypeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Enums\Payment\PaymentGatewayEnum;
use App\Livewire\Frontend\Auth\Login;
use App\Livewire\Frontend\Cart\Checkout;
use App\Livewire\Frontend\Cart\Counter;
use App\Livewire\Frontend\Cart\Index;
use App\Livewire\Frontend\Orders\Track;
use App\Mail\Order\OrderPlacedMail;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Frontend\Shop\OrderService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

test('guests can add products to the cart and open checkout', function () {
    $product = Product::factory()->create(['name' => 'Guest Cart Product']);

    Livewire::test(Counter::class)
        ->call('add', $product->id)
        ->assertNoRedirect()
        ->assertNotDispatched('modal-show')
        ->assertDispatched('toast-show');

    expect(session('cart'))->toBe([$product->id => 1]);

    Livewire::test(Index::class)->assertSee('Guest Cart Product');
    $this->get(route('checkout'))->assertOk();
});

test('archived and out of stock products cannot be added to the cart', function () {
    $archived = Product::factory()->archived()->create();
    $soldOut = Product::factory()->outOfStock()->create();

    Livewire::test(Counter::class)
        ->call('add', $archived->id)
        ->call('add', $soldOut->id)
        ->assertDispatched('toast-show');

    expect(session('cart', []))->toBe([]);
});

test('staff accounts can add to the cart and place an order', function () {
    $staff = User::factory()->superAdmin()->create();
    $product = Product::factory()->create();

    Livewire::actingAs($staff)->test(Counter::class)->call('add', $product->id);

    Livewire::actingAs($staff)->test(Checkout::class)
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Order::query()->sole()->user_id)->toBe($staff->id)
        ->and($staff->notifications()->where('type', NotificationTypeEnum::OrderPlaced->value)->count())->toBe(1);
});

test('cart quantities cannot exceed the stock', function () {
    $product = Product::factory()->stock(2)->create();

    Livewire::test(Counter::class)->call('add', $product->id);

    Livewire::test(Index::class)
        ->call('updateQuantity', $product->id, 2)
        ->call('updateQuantity', $product->id, 3)
        ->assertDispatched('toast-show');

    expect(session('cart'))->toBe([$product->id => 2]);
});

test('checkout places a pending order with the live offer applied and empties the cart', function () {
    $customer = User::factory()->create();
    $digital = Product::factory()->stock(5)->create(['price' => 400, 'discount_percentage' => 25]);
    $printed = Product::factory()->stock(5)->create(['price' => 200]);
    Offer::factory()->create(['name' => 'Test', 'discount_percentage' => 10]);

    Livewire::actingAs($customer)->test(Counter::class)
        ->call('add', $digital->id)
        ->call('add', $printed->id)
        ->call('add', $printed->id);

    Livewire::actingAs($customer)->test(Checkout::class)
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect();

    $order = Order::query()->with('items')->sole();

    expect($order->user_id)->toBe($customer->id)
        ->and($order->subtotal)->toBe(700)
        ->and($order->discount_amount)->toBe(70)
        ->and($order->total)->toBe(630)
        ->and($order->status)->toBe(OrderStatusEnum::Pending)
        ->and($order->payment_status)->toBe(PaymentStatusEnum::Pending)
        ->and($order->currency)->toBe(appCurrency())
        ->and($order->shipping_address)->toBe('Mirpur, Dhaka')
        ->and($digital->refresh()->quantity)->toBe(4)
        ->and(Payment::query()->count())->toBe(0)
        ->and($order->items)->toHaveCount(2)
        ->and($printed->refresh()->quantity)->toBe(3)
        ->and(session('cart', []))->toBe([])
        ->and($customer->refresh()->phone)->toBe('01712345678')
        ->and($order->applied_offers)->toBe([['name' => 'Test', 'percentage' => 10, 'categories' => [], 'amount' => 70]])
        ->and($order->items->pluck('offer_name_snapshot')->unique()->all())->toBe(['Test'])
        ->and($order->items->pluck('offer_discount_percentage')->unique()->all())->toBe([10]);

    $this->actingAs($customer)->get(route('user.orders.detail', $order))->assertOk()->assertSee('Test (10% off)');
});

test('completed order steps are all marked as reached', function () {
    $steps = OrderStatusEnum::Completed->steps();

    expect($steps)
        ->toHaveCount(4)
        ->and(array_column($steps, 'reached'))->toBe([true, true, true, true])
        ->and(array_column($steps, 'current'))->toBe([false, false, false, false]);
});

test('order amounts are rounded to whole currency units', function () {
    config(['app.currency_symbol' => 'Tk ', 'shop.support_email' => 'support@example.com', 'payment.enabled' => [PaymentGatewayEnum::Manual->value]]);
    $customer = User::factory()->create();
    $product = Product::factory()->create(['price' => 605, 'discount_percentage' => 5]);
    Offer::factory()->create(['discount_percentage' => 10]);

    Livewire::actingAs($customer)->test(Counter::class)->call('add', $product->id);

    Livewire::actingAs($customer)->test(Checkout::class)
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->with('items')->sole();
    $emailLink = collect(app(OrderService::class)->contactLinks($order))->firstWhere('icon', 'mail');

    expect($order->items->sole()->line_total)->toBe(575)
        ->and($order->items->sole()->sku_snapshot)->toBe($product->sku)
        ->and($order->subtotal)->toBe(575)
        ->and($order->discount_amount)->toBe(58)
        ->and($order->total)->toBe(517)
        ->and(rawurldecode($emailLink['href']))->toContain('Total: Tk 517');
});

test('guests can place an order with their name and email', function () {
    Mail::fake();
    $this->withoutDefer();
    $product = Product::factory()->stock(3)->create();

    Livewire::test(Counter::class)->call('add', $product->id);

    Livewire::test(Checkout::class)
        ->assertSee('Place Order')
        ->set('name', 'Guest Buyer')
        ->set('email', 'Guest@Example.com')
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->set('billingSameAsShipping', false)
        ->set('billingAddress', 'Gulshan, Dhaka')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect();

    $order = Order::query()->sole();

    expect($order->user_id)->toBeNull()
        ->and($order->customer_name)->toBe('Guest Buyer')
        ->and($order->customer_email)->toBe('guest@example.com')
        ->and($order->shipping_address)->toBe('Mirpur, Dhaka')
        ->and($order->billing_address)->toBe('Gulshan, Dhaka')
        ->and($product->refresh()->quantity)->toBe(2)
        ->and(session('cart', []))->toBe([]);

    Mail::assertSent(OrderPlacedMail::class, fn (OrderPlacedMail $mail): bool => $mail->hasTo('guest@example.com'));
});

test('guests can create an account while placing an order', function () {
    Livewire::test(Counter::class)->call('add', Product::factory()->create()->id);

    Livewire::test(Checkout::class)
        ->set('name', 'New Buyer')
        ->set('email', 'new@example.com')
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->set('createAccount', true)
        ->set('password', 'Secret-pass-123')
        ->set('password_confirmation', 'Secret-pass-123')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'new@example.com')->sole();

    expect($user->hasVerifiedEmail())->toBeFalse()
        ->and(Order::query()->sole()->customer_email)->toBe('new@example.com');
});

test('guests cannot create an account at checkout with an email that is taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    Livewire::test(Counter::class)->call('add', Product::factory()->create()->id);

    Livewire::test(Checkout::class)
        ->set('name', 'New Buyer')
        ->set('email', 'taken@example.com')
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->set('createAccount', true)
        ->set('password', 'Secret-pass-123')
        ->set('password_confirmation', 'Secret-pass-123')
        ->call('placeOrder')
        ->assertHasErrors('email');

    expect(Order::count())->toBe(0);
});

test('no account is created when the order cannot be placed', function () {
    $product = Product::factory()->stock(1)->create();
    Livewire::test(Counter::class)->call('add', $product->id);
    $product->update(['quantity' => 0]);

    Livewire::test(Checkout::class)
        ->set('name', 'New Buyer')
        ->set('email', 'new@example.com')
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->set('createAccount', true)
        ->set('password', 'Secret-pass-123')
        ->set('password_confirmation', 'Secret-pass-123')
        ->call('placeOrder')
        ->assertHasErrors('cart');

    expect(Order::count())->toBe(0)
        ->and(User::query()->where('email', 'new@example.com')->exists())->toBeFalse();
});

test('guest orders join a verified account when that customer logs in', function () {
    $user = User::factory()->create(['email' => 'buyer@example.com', 'password' => 'Secret-pass-123']);
    $order = Order::factory()->create(['user_id' => null, 'customer_email' => 'buyer@example.com']);

    Livewire::test(Login::class)
        ->set('email', 'buyer@example.com')
        ->set('password', 'Secret-pass-123')
        ->call('login')
        ->assertHasNoErrors();

    expect($order->refresh()->user_id)->toBe($user->id);
});

test('guests must give a name and email to place an order', function () {
    Livewire::test(Counter::class)->call('add', Product::factory()->create()->id);

    Livewire::test(Checkout::class)
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->call('placeOrder')
        ->assertHasErrors(['name' => 'required', 'email' => 'required']);

    expect(Order::count())->toBe(0);
});

test('checkout prefills the saved addresses and uses the delivery address for billing when marked the same', function () {
    $customer = User::factory()->create(['phone' => '01712345678', 'shipping_address' => 'Mirpur, Dhaka', 'billing_address' => 'Gulshan, Dhaka']);
    Livewire::actingAs($customer)->test(Counter::class)->call('add', Product::factory()->create()->id);

    Livewire::actingAs($customer)->test(Checkout::class)
        ->assertSet('shippingAddress', 'Mirpur, Dhaka')
        ->assertSet('billingAddress', 'Gulshan, Dhaka')
        ->assertSet('billingSameAsShipping', false)
        ->set('billingSameAsShipping', true)
        ->call('placeOrder')
        ->assertHasNoErrors();

    expect(Order::query()->sole()->billing_address)->toBe('Mirpur, Dhaka');
});

test('checkout requires a delivery address', function () {
    $customer = User::factory()->create();
    Livewire::actingAs($customer)->test(Counter::class)->call('add', Product::factory()->create()->id);

    Livewire::actingAs($customer)->test(Checkout::class)
        ->set('phone', '01712345678')
        ->set('shippingAddress', '')
        ->call('placeOrder')
        ->assertHasErrors(['shippingAddress' => 'required']);

    expect(Order::count())->toBe(0);
});

test('checkout with a phone number another user owns places the order without copying it to the profile', function () {
    User::factory()->create(['phone' => '01712345678']);
    $customer = User::factory()->create();
    $product = Product::factory()->create();

    Livewire::actingAs($customer)->test(Counter::class)->call('add', $product->id);

    Livewire::actingAs($customer)->test(Checkout::class)
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->call('placeOrder')
        ->assertHasNoErrors();

    expect(Order::query()->sole()->phone)->toBe('01712345678')
        ->and($customer->refresh()->phone)->toBeNull();
});

test('checkout rejects an invalid phone', function () {
    $customer = User::factory()->create();
    $product = Product::factory()->create();

    Livewire::actingAs($customer)->test(Counter::class)->call('add', $product->id);

    Livewire::actingAs($customer)->test(Checkout::class)
        ->set('phone', '12345')
        ->call('placeOrder')
        ->assertHasErrors(['phone' => 'regex']);
});

test('the order confirmation page needs a valid signature', function () {
    $order = Order::factory()->create();

    $this->get(route('orders.confirmation', $order->public_order_id))->assertForbidden();
    $this->get(URL::signedRoute('orders.confirmation', $order->public_order_id))->assertOk()->assertSee($order->public_order_id);
});

test('customers can only view their own orders', function () {
    $customer = User::factory()->create();
    $mine = Order::factory()->for($customer)->create();
    $theirs = Order::factory()->create();

    $this->actingAs($customer)->get(route('user.orders.detail', $mine->public_order_id))->assertOk()->assertSee($mine->public_order_id);
    $this->actingAs($customer)->get(route('user.orders.detail', $theirs->public_order_id))->assertNotFound();
    $this->actingAs($customer)->get(route('user.orders.invoice', $theirs->public_order_id))->assertNotFound();
});

test('customers can download a compact invoice for a paid order', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->paid()->create(['customer_name' => 'নতুন ক্রেতা']);

    $response = $this->actingAs($customer)->get(route('user.orders.invoice', $order->public_order_id))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect(strlen((string) $response->getContent()))->toBeLessThan(150_000)
        ->and($response->getContent())->toContain('HindSiliguri')->not->toContain('FreeSerif');
});

test('signed in customers get an order email without a password link', function () {
    Mail::fake();
    $this->withoutDefer();
    $customer = User::factory()->create();

    Livewire::actingAs($customer)->test(Counter::class)->call('add', Product::factory()->create()->id);
    Livewire::actingAs($customer)->test(Checkout::class)->set('phone', '01712345678')->set('shippingAddress', 'Mirpur, Dhaka')->call('placeOrder')->assertHasNoErrors();

    Mail::assertSent(OrderPlacedMail::class, fn (OrderPlacedMail $mail): bool => $mail->hasTo($customer->email));
});

test('anyone can track an order with its id and the order email only', function () {
    $customer = User::factory()->create(['email' => 'buyer@example.com']);
    $order = Order::factory()->for($customer)->create(['public_order_id' => 'AE-TRACK1']);

    Livewire::test(Track::class)
        ->set('orderId', 'ae-track1')
        ->set('email', 'someone@example.com')
        ->call('track')
        ->assertHasErrors('orderId')
        ->assertSet('foundOrderId', null)
        ->set('email', 'buyer@example.com')
        ->call('track')
        ->assertHasNoErrors()
        ->assertSet('foundOrderId', $order->id)
        ->assertSee('AE-TRACK1');

    $this->get(route('orders.track'))->assertOk();
});

test('a guest order joins the account once that email is verified', function () {
    $order = Order::factory()->create(['user_id' => null, 'customer_name' => 'Later Buyer', 'customer_email' => 'later@example.com']);
    $user = User::factory()->unverified()->create(['email' => 'later@example.com']);

    expect(app(OrderService::class)->claimGuestOrders($user))->toBe(0);

    $this->get(URL::temporarySignedRoute('user.verification.verify', now()->addMinutes(5), ['id' => $user->id, 'hash' => sha1($user->email)]));

    expect($order->refresh()->user_id)->toBe($user->id);
});

test('each product gets the best live offer that covers its category', function () {
    $customer = User::factory()->create();
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $grandchild = Category::factory()->create(['parent_id' => $child->id]);
    $inChild = Product::factory()->create(['price' => 300, 'discount_percentage' => null, 'category_id' => $grandchild->id]);
    $elsewhere = Product::factory()->create(['price' => 200, 'discount_percentage' => null]);
    Offer::factory()->create(['discount_percentage' => 5]);
    Offer::factory()->create(['type' => OfferTypeEnum::Specific, 'discount_percentage' => 20])->categories()->attach($parent);

    Livewire::actingAs($customer)->test(Counter::class)
        ->call('add', $inChild->id)
        ->call('add', $elsewhere->id);

    Livewire::actingAs($customer)->test(Checkout::class)
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->sole();

    expect($order->subtotal)->toBe(500)
        ->and($order->discount_amount)->toBe(70)
        ->and($order->total)->toBe(430);
});

test('order detail shows an alert for every status', function (OrderStatusEnum $status) {
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create(['status' => $status]);

    $this->actingAs($customer)->get(route('user.orders.detail', $order->public_order_id))
        ->assertOk()
        ->assertSee($status->notice()['heading']);
})->with(OrderStatusEnum::cases());

test('order detail shows the thumbnails of the ordered products', function () {
    Storage::fake('public');
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create();

    foreach (['first', 'second'] as $name) {
        $product = Product::factory()->create();
        $product->attachMedia('product.media.images', UploadedFile::fake()->image("{$name}.jpg", 500, 500));
        $order->items()->create(['product_id' => $product->id, 'product_name_snapshot' => $product->name, 'quantity' => 1, 'unit_price' => 1, 'line_total' => 1]);
    }

    $order = app(OrderService::class)->getOrderWithItems($order->fresh());

    expect($order->items->every(fn ($item): bool => $item->product->relationLoaded('media') && filled($item->product->thumbnail_url)))->toBeTrue();

    $this->actingAs($customer)->get(route('user.orders.detail', $order->public_order_id))
        ->assertOk()
        ->assertSee($order->items->first()->product->thumbnail_url, false);
});

test('manual payment instructions only show for orders paid by bKash or Nagad', function () {
    config(['shop.support_email' => 'support@example.com']);
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create();

    expect(app(OrderService::class)->contactLinks($order))->toBe([]);

    $this->actingAs($customer)->get(route('user.orders.detail', $order->public_order_id))
        ->assertOk()
        ->assertDontSee(__('Contact us with your order ID. We share the bKash or Nagad payment details and confirm the payment.'));

    Payment::factory()->for($order)->create();
    $order->refresh();

    expect(app(OrderService::class)->contactLinks($order))->not->toBe([]);

    $this->actingAs($customer)->get(route('user.orders.detail', $order->public_order_id))
        ->assertOk()
        ->assertSee(__('Contact us with your order ID. We share the bKash or Nagad payment details and confirm the payment.'));
});

test('with a payment process checkout opens a pending payment for the order total', function () {
    config(['payment.enabled' => [PaymentGatewayEnum::Manual->value]]);
    $customer = User::factory()->create();

    Livewire::actingAs($customer)->test(Counter::class)->call('add', Product::factory()->create(['price' => 300, 'discount_percentage' => null])->id);
    Livewire::actingAs($customer)->test(Checkout::class)
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect();

    $payment = Payment::query()->sole();

    expect($payment->order_id)->toBe(Order::query()->sole()->id)
        ->and($payment->gateway)->toBe(PaymentGatewayEnum::Manual)
        ->and($payment->status)->toBe(PaymentStatusEnum::Pending)
        ->and($payment->amount)->toBe(300)
        ->and($payment->reference)->not->toBeEmpty();
});
