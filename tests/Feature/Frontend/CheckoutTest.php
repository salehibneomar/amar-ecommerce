<?php

use App\Enums\Content\OfferTypeEnum;
use App\Enums\Notification\NotificationTypeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Livewire\Frontend\Cart\Checkout;
use App\Livewire\Frontend\Cart\Counter;
use App\Livewire\Frontend\Cart\Index;
use App\Livewire\Frontend\Orders\Track;
use App\Mail\Order\OrderPlacedMail;
use App\Models\Book;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use App\Services\Frontend\Shop\OrderService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

test('guests can add books to the cart and open checkout', function () {
    $product = Book::factory()->create(['name' => 'Guest Cart Book']);

    Livewire::test(Counter::class)
        ->call('add', $product->id)
        ->assertNoRedirect()
        ->assertNotDispatched('modal-show')
        ->assertDispatched('toast-show');

    expect(session('cart'))->toBe([$product->id => 1]);

    Livewire::test(Index::class)->assertSee('Guest Cart Book');
    $this->get(route('checkout'))->assertOk();
});

test('a digital book stays at quantity one however often it is added', function () {
    $product = Book::factory()->create();

    Livewire::test(Counter::class)
        ->call('add', $product->id)
        ->call('add', $product->id);

    expect(session('cart'))->toBe([$product->id => 1]);
});

test('free, archived and already owned books cannot be added to the cart', function () {
    $customer = User::factory()->create();
    $free = Book::factory()->free()->create();
    $archived = Book::factory()->archived()->create();
    $owned = Book::factory()->create();
    Order::factory()->for($customer)->paid()->create()->items()->create([
        'product_id' => $owned->id,
        'product_name_snapshot' => $owned->name,
        'quantity' => 1,
        'unit_price' => $owned->price,
        'line_total' => $owned->price,
    ]);

    Livewire::actingAs($customer)->test(Counter::class)
        ->call('add', $free->id)
        ->call('add', $archived->id)
        ->call('add', $owned->id)
        ->assertDispatched('toast-show');

    expect(session('cart', []))->toBe([]);
});

test('staff accounts can add to the cart and place an order', function () {
    $staff = User::factory()->superAdmin()->create();
    $product = Book::factory()->create();

    Livewire::actingAs($staff)->test(Counter::class)->call('add', $product->id);

    Livewire::actingAs($staff)->test(Checkout::class)
        ->set('phone', '01712345678')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Order::query()->sole()->user_id)->toBe($staff->id)
        ->and($staff->notifications()->where('type', NotificationTypeEnum::OrderPlaced->value)->count())->toBe(1);
});

test('printed book quantities cannot exceed the stock', function () {
    $product = Book::factory()->physical(2)->create();

    Livewire::test(Counter::class)->call('add', $product->id);

    Livewire::test(Index::class)
        ->call('updateQuantity', $product->id, 2)
        ->call('updateQuantity', $product->id, 3)
        ->assertDispatched('toast-show');

    expect(session('cart'))->toBe([$product->id => 2]);
});

test('checkout places a requested order with the live offer applied and empties the cart', function () {
    $customer = User::factory()->create();
    $digital = Book::factory()->create(['price' => 400, 'discount_percentage' => 25]);
    $printed = Book::factory()->physical(5)->create(['price' => 200]);
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
        ->and($order->status)->toBe(OrderStatusEnum::Requested)
        ->and($order->payment_status)->toBe(PaymentStatusEnum::Pending)
        ->and($order->transaction_id)->toBeNull()
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
        ->toHaveCount(3)
        ->and(array_column($steps, 'reached'))->toBe([true, true, true])
        ->and(array_column($steps, 'current'))->toBe([false, false, false]);
});

test('order amounts are rounded to whole currency units', function () {
    config(['app.currency_symbol' => 'Tk ', 'shop.support_email' => 'support@example.com']);
    $customer = User::factory()->create();
    $product = Book::factory()->create(['price' => 605, 'discount_percentage' => 5]);
    Offer::factory()->create(['discount_percentage' => 10]);

    Livewire::actingAs($customer)->test(Counter::class)->call('add', $product->id);

    Livewire::actingAs($customer)->test(Checkout::class)
        ->set('phone', '01712345678')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->with('items')->sole();
    $emailLink = collect(app(OrderService::class)->contactLinks($order))->firstWhere('icon', 'mail');

    expect($order->items->sole()->line_total)->toBe(575)
        ->and($order->subtotal)->toBe(575)
        ->and($order->discount_amount)->toBe(58)
        ->and($order->total)->toBe(517)
        ->and(rawurldecode($emailLink['href']))->toContain('Total: Tk 517');
});

test('guests must create an account before placing an order', function () {
    $product = Book::factory()->create();

    Livewire::test(Counter::class)->call('add', $product->id);

    Livewire::test(Checkout::class)
        ->call('placeOrder')
        ->assertRedirect(route('user.register'));

    expect(Order::count())->toBe(0)
        ->and(session('cart'))->toBe([$product->id => 1]);

    Livewire::test(Checkout::class)
        ->assertSee('Create a free account')
        ->assertSee('Create Account')
        ->assertSee('Log in')
        ->assertDontSee('Place order');
});

test('checkout requires an address only when the cart has printed books', function () {
    $customer = User::factory()->create();
    Livewire::actingAs($customer)->test(Counter::class)->call('add', Book::factory()->physical()->create()->id);

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
    $product = Book::factory()->create();

    Livewire::actingAs($customer)->test(Counter::class)->call('add', $product->id);

    Livewire::actingAs($customer)->test(Checkout::class)
        ->set('phone', '01712345678')
        ->call('placeOrder')
        ->assertHasNoErrors();

    expect(Order::query()->sole()->phone)->toBe('01712345678')
        ->and($customer->refresh()->phone)->toBeNull();
});

test('checkout rejects an invalid phone', function () {
    $customer = User::factory()->create();
    $product = Book::factory()->create();

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
    $order = Order::factory()->for($customer)->paid()->create(['customer_name' => 'নতুন কৃষক']);

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

    Livewire::actingAs($customer)->test(Counter::class)->call('add', Book::factory()->create()->id);
    Livewire::actingAs($customer)->test(Checkout::class)->set('phone', '01712345678')->call('placeOrder')->assertHasNoErrors();

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

test('each book gets the best live offer that covers its category', function () {
    $customer = User::factory()->create();
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $grandchild = Category::factory()->create(['parent_id' => $child->id]);
    $inChild = Book::factory()->create(['price' => 300, 'discount_percentage' => null, 'category_id' => $grandchild->id]);
    $elsewhere = Book::factory()->create(['price' => 200, 'discount_percentage' => null]);
    Offer::factory()->create(['discount_percentage' => 5]);
    Offer::factory()->create(['type' => OfferTypeEnum::Specific, 'discount_percentage' => 20])->categories()->attach($parent);

    Livewire::actingAs($customer)->test(Counter::class)
        ->call('add', $inChild->id)
        ->call('add', $elsewhere->id);

    Livewire::actingAs($customer)->test(Checkout::class)
        ->set('phone', '01712345678')
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

test('order detail shows the covers of the ordered books', function () {
    Storage::fake('public');
    $customer = User::factory()->create();
    $order = Order::factory()->for($customer)->create();

    foreach (['first', 'second'] as $name) {
        $book = Book::factory()->create();
        $book->attachMedia('book.media.cover', UploadedFile::fake()->image("{$name}.jpg", 300, 450));
        $order->items()->create(['product_id' => $book->id, 'product_name_snapshot' => $book->name, 'quantity' => 1, 'unit_price' => 1, 'line_total' => 1]);
    }

    $order = app(OrderService::class)->getOrderWithItems($order->fresh());

    expect($order->items->every(fn ($item): bool => $item->product->relationLoaded('media') && filled($item->product->cover_thumbnail_url)))->toBeTrue();

    $this->actingAs($customer)->get(route('user.orders.detail', $order->public_order_id))
        ->assertOk()
        ->assertSee($order->items->first()->product->cover_thumbnail_url, false);
});
