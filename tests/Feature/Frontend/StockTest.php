<?php

use App\Enums\Catalog\ProductStatusEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Livewire\Frontend\Cart\Checkout;
use App\Livewire\Frontend\Cart\Counter;
use App\Livewire\Frontend\Shop\Index as ShopIndex;
use App\Models\Book;
use App\Models\User;
use App\Traits\Catalog\StockTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function productsCacheIsWarm(): bool
{
    return Cache::store(frontendCacheStore())->has(FrontendCacheKeyEnum::Products->value);
}

function stock(): object
{
    return new class
    {
        use StockTrait {
            takeStock as public;
            restoreStock as public;
        }
    };
}

function warmProductsCache(): void
{
    Cache::store(frontendCacheStore())->put(FrontendCacheKeyEnum::Products->value, 'cached');
}

test('a normal sale takes stock without clearing the product cache', function () {
    $book = Book::factory()->physical(5)->create();
    warmProductsCache();

    stock()->takeStock($book, 2);

    expect($book->refresh()->quantity)->toBe(3)
        ->and($book->status)->toBe(ProductStatusEnum::InStock)
        ->and(productsCacheIsWarm())->toBeTrue();
});

test('selling the last copy marks the book out of stock and clears the product cache', function () {
    $customer = User::factory()->create();
    $book = Book::factory()->physical(1)->create();

    Livewire::actingAs($customer)->test(Counter::class)->call('add', $book->id);
    warmProductsCache();

    Livewire::actingAs($customer)->test(Checkout::class)
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->call('placeOrder')
        ->assertHasNoErrors();

    expect($book->refresh()->quantity)->toBe(0)
        ->and($book->status)->toBe(ProductStatusEnum::OutOfStock)
        ->and(productsCacheIsWarm())->toBeFalse();
});

test('stock can never be oversold', function () {
    $book = Book::factory()->physical(2)->create(['name' => 'Rare Book']);

    expect(fn () => stock()->takeStock($book, 3))->toThrow(ValidationException::class, 'Only 2 copies of Rare Book are left.')
        ->and($book->refresh()->quantity)->toBe(2);
});

test('returned stock re-lists only books that had run out', function () {
    $soldOut = Book::factory()->physical(1)->create();
    $hidden = Book::factory()->physical(4)->create(['status' => ProductStatusEnum::OutOfStock]);
    $digital = Book::factory()->create();
    $service = stock();

    $service->takeStock($soldOut, 1);
    warmProductsCache();
    $service->restoreStock($soldOut->id, 1);
    $service->restoreStock($hidden->id, 1);
    $service->restoreStock($digital->id, 1);

    expect($soldOut->refresh()->status)->toBe(ProductStatusEnum::InStock)
        ->and($soldOut->quantity)->toBe(1)
        ->and(productsCacheIsWarm())->toBeFalse()
        ->and($hidden->refresh()->status)->toBe(ProductStatusEnum::OutOfStock)
        ->and($hidden->quantity)->toBe(5)
        ->and($digital->refresh()->quantity)->toBeNull();
});

test('sold out books stay listed in the shop with an out of stock badge but cannot be bought', function () {
    $book = Book::factory()->physical(1)->create(['name' => 'Sold Out Book']);
    stock()->takeStock($book, 1);

    Livewire::test(ShopIndex::class)
        ->assertSee(['Sold Out Book', 'Out of Stock'])
        ->assertDontSeeHtml("productId: {$book->id} })");

    Livewire::test(Counter::class)->call('add', $book->id);

    expect(session('cart', []))->toBe([]);
});
