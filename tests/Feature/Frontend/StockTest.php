<?php

use App\Enums\Catalog\ProductStatusEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Livewire\Frontend\Cart\Checkout;
use App\Livewire\Frontend\Cart\Counter;
use App\Livewire\Frontend\Shop\Index as ShopIndex;
use App\Models\Product;
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
    $product = Product::factory()->stock(5)->create();
    warmProductsCache();

    stock()->takeStock($product, 2);

    expect($product->refresh()->quantity)->toBe(3)
        ->and($product->status)->toBe(ProductStatusEnum::InStock)
        ->and(productsCacheIsWarm())->toBeTrue();
});

test('selling the last item marks the product out of stock and clears the product cache', function () {
    $customer = User::factory()->create();
    $product = Product::factory()->stock(1)->create();

    Livewire::actingAs($customer)->test(Counter::class)->call('add', $product->id);
    warmProductsCache();

    Livewire::actingAs($customer)->test(Checkout::class)
        ->set('phone', '01712345678')
        ->set('shippingAddress', 'Mirpur, Dhaka')
        ->call('placeOrder')
        ->assertHasNoErrors();

    expect($product->refresh()->quantity)->toBe(0)
        ->and($product->status)->toBe(ProductStatusEnum::OutOfStock)
        ->and(productsCacheIsWarm())->toBeFalse();
});

test('stock can never be oversold', function () {
    $product = Product::factory()->stock(2)->create(['name' => 'Rare Product']);

    expect(fn () => stock()->takeStock($product, 3))->toThrow(ValidationException::class, 'Only 2 of Rare Product are left.')
        ->and($product->refresh()->quantity)->toBe(2);
});

test('returned stock re-lists only products that had run out', function () {
    $soldOut = Product::factory()->stock(1)->create();
    $hidden = Product::factory()->stock(4)->create(['status' => ProductStatusEnum::OutOfStock]);
    $service = stock();

    $service->takeStock($soldOut, 1);
    warmProductsCache();
    $service->restoreStock($soldOut->id, 1);
    $service->restoreStock($hidden->id, 1);

    expect($soldOut->refresh()->status)->toBe(ProductStatusEnum::InStock)
        ->and($soldOut->quantity)->toBe(1)
        ->and(productsCacheIsWarm())->toBeFalse()
        ->and($hidden->refresh()->status)->toBe(ProductStatusEnum::OutOfStock)
        ->and($hidden->quantity)->toBe(5);
});

test('sold out products stay listed in the shop with an out of stock badge but cannot be bought', function () {
    $product = Product::factory()->stock(1)->create(['name' => 'Sold Out Product']);
    stock()->takeStock($product, 1);

    Livewire::test(ShopIndex::class)
        ->assertSee(['Sold Out Product', 'Out of Stock'])
        ->assertDontSeeHtml("productId: {$product->id} })");

    Livewire::test(Counter::class)->call('add', $product->id);

    expect(session('cart', []))->toBe([]);
});
