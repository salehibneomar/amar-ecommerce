<?php

use App\Enums\Catalog\ProductStatusEnum;
use App\Models\Book;
use App\Models\User;

test('public pages carry the default description and link preview tags', function () {
    config(['seo.description' => 'Books for farmers', 'seo.image' => 'images/share.png']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="description" content="Books for farmers" />', false)
        ->assertSee('<meta property="og:type" content="website" />', false)
        ->assertSee('<link rel="canonical" href="'.route('home').'" />', false)
        ->assertSee('<meta property="og:image" content="'.url('images/share.png').'" />', false)
        ->assertDontSee('noindex');
});

test('book pages use their own description and the product preview type', function () {
    $product = Book::factory()->create(['name' => 'Mango Cultivation', 'description' => '<p>Grow sweet <strong>mangoes</strong> at home.</p>']);

    $this->get(route('shop.product', $product->slug))
        ->assertOk()
        ->assertSee('<meta name="description" content="Grow sweet mangoes at home." />', false)
        ->assertSee('<meta property="og:title" content="Mango Cultivation" />', false)
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

test('the sitemap lists public pages and in-stock and out-of-stock books, never archived ones', function () {
    $product = Book::factory()->create();
    $outOfStock = Book::factory()->create(['status' => ProductStatusEnum::OutOfStock]);
    $archived = Book::factory()->archived()->create();

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee(route('shop'), false)
        ->assertSee(route('shop.product', $product->slug), false)
        ->assertSee(route('shop.product', $outOfStock->slug), false)
        ->assertDontSee(route('shop.product', $archived->slug), false);
});
