<?php

use App\Enums\System\FrontendCacheKeyEnum;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;
use App\Services\Frontend\Shop\PageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function cacheFrontendKeys(FrontendCacheKeyEnum ...$keys): void
{
    foreach ($keys as $key) {
        Cache::store(frontendCacheStore())->put($key->value, 'cached');
    }
}

function frontendKeyIsCached(FrontendCacheKeyEnum $key): bool
{
    return Cache::store(frontendCacheStore())->has($key->value);
}

test('turning the frontend cache off reads the database on every request', function () {
    config(['cache.frontend.enabled' => false]);
    $service = app(PageService::class);
    $service->getFeaturedProducts();

    DB::enableQueryLog();
    $service->getFeaturedProducts();
    $service->searchProducts(page: 1);

    expect(DB::getQueryLog())->not->toBe([])
        ->and(Cache::store(frontendCacheStore())->has(FrontendCacheKeyEnum::Products->versionKey()))->toBeFalse();
});

test('saving or deleting a cached model clears its storefront cache keys', function () {
    $product = Product::factory()->create();
    cacheFrontendKeys(FrontendCacheKeyEnum::Products, FrontendCacheKeyEnum::Faqs);

    $product->update(['name' => 'Renamed']);

    expect(frontendKeyIsCached(FrontendCacheKeyEnum::Products))->toBeFalse()
        ->and(frontendKeyIsCached(FrontendCacheKeyEnum::Faqs))->toBeTrue();

    cacheFrontendKeys(FrontendCacheKeyEnum::Faqs);
    Faq::factory()->create()->delete();

    expect(frontendKeyIsCached(FrontendCacheKeyEnum::Faqs))->toBeFalse();
});

test('a category change clears categories, products and offers', function () {
    $category = Category::factory()->create();
    cacheFrontendKeys(FrontendCacheKeyEnum::Categories, FrontendCacheKeyEnum::Products, FrontendCacheKeyEnum::Offers);

    $category->update(['name' => 'Renamed']);

    expect(frontendKeyIsCached(FrontendCacheKeyEnum::Categories))->toBeFalse()
        ->and(frontendKeyIsCached(FrontendCacheKeyEnum::Products))->toBeFalse()
        ->and(frontendKeyIsCached(FrontendCacheKeyEnum::Offers))->toBeFalse();
});

test('stock-only changes keep the storefront cache', function () {
    $product = Product::factory()->create(['quantity' => 5]);
    cacheFrontendKeys(FrontendCacheKeyEnum::Products);

    $product->decrement('quantity');

    expect(frontendKeyIsCached(FrontendCacheKeyEnum::Products))->toBeTrue();
});

test('uploading media clears the owner model cache', function () {
    Storage::fake('public');
    $product = Product::factory()->create();
    cacheFrontendKeys(FrontendCacheKeyEnum::Products);

    $product->syncMedia('product.media.images', UploadedFile::fake()->image('photo.jpg'));

    expect(frontendKeyIsCached(FrontendCacheKeyEnum::Products))->toBeFalse();
});

test('inside a transaction the cache is cleared only after commit', function () {
    $product = Product::factory()->create();
    cacheFrontendKeys(FrontendCacheKeyEnum::Products);

    DB::transaction(function () use ($product): void {
        $product->update(['name' => 'In transaction']);

        expect(frontendKeyIsCached(FrontendCacheKeyEnum::Products))->toBeTrue();
    });

    expect(frontendKeyIsCached(FrontendCacheKeyEnum::Products))->toBeFalse();
});
