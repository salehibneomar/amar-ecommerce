<?php

use App\Enums\Catalog\ProductStatusEnum;
use App\Models\Product;
use App\Models\User;

test('public pages carry the default description and link preview tags', function () {
    config(['seo.description' => 'Products for everyone', 'seo.image' => 'images/share.png']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="description" content="Products for everyone" />', false)
        ->assertSee('<meta property="og:type" content="website" />', false)
        ->assertSee('<link rel="canonical" href="'.route('home').'" />', false)
        ->assertSee('<meta property="og:image" content="'.url('images/share.png').'" />', false)
        ->assertDontSee('noindex');
});

test('product pages use their own description and the product preview type', function () {
    $product = Product::factory()->create(['name' => 'Mango Juice', 'description' => '<p>Cold pressed <strong>mangoes</strong> with no added sugar.</p>']);

    $this->get(route('shop.product', $product->slug))
        ->assertOk()
        ->assertSee('<meta name="description" content="Cold pressed mangoes with no added sugar." />', false)
        ->assertSee('<meta property="og:title" content="Mango Juice" />', false)
        ->assertSee('<meta property="og:type" content="product" />', false);
});

test('admin pages are kept out of search engines', function () {
    $this->get(route('login'))->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
    $this->actingAs(User::factory()->superAdmin()->create())->get(route('dashboard'))->assertSee('noindex, nofollow');
});

test('turning indexing off hides every page from search engines', function () {
    config(['seo.indexing_enabled' => false]);

    $this->get(route('home'))->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
});

test('the sitemap lists public pages and in-stock and out-of-stock products, never archived ones', function () {
    $product = Product::factory()->create();
    $outOfStock = Product::factory()->create(['status' => ProductStatusEnum::OutOfStock]);
    $archived = Product::factory()->archived()->create();

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee(route('shop'), false)
        ->assertSee(route('shop.product', $product->slug), false)
        ->assertSee(route('shop.product', $outOfStock->slug), false)
        ->assertDontSee(route('shop.product', $archived->slug), false);
});
