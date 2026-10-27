<?php

use App\Enums\Catalog\ProductStatusEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

test('robots.txt points search engines to the sitemap and skips private areas', function () {
    $this->get(route('robots'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Sitemap: '.route('sitemap'), false)
        ->assertSee('Disallow: /'.adminRoutePrefix(), false);
});

test('turning indexing off blocks crawlers and hides the sitemap', function () {
    config(['seo.indexing_enabled' => false]);

    $this->get(route('robots'))->assertOk()->assertSee("Disallow: /\n", false)->assertDontSee('Sitemap:');
    $this->get(route('sitemap'))->assertNotFound();
});

test('public pages carry the google verification tag when it is set', function () {
    $this->get(route('home'))->assertDontSee('google-site-verification');

    config(['seo.google_site_verification' => 'abc123']);

    $this->get(route('home'))->assertSee('<meta name="google-site-verification" content="abc123" />', false);
    $this->get(route('login'))->assertDontSee('google-site-verification');
});

test('product pages prefer their own seo, then the product, then the site defaults', function () {
    Storage::fake('public');
    $product = Product::factory()->create(['name' => 'Mango Juice', 'description' => null]);
    $seoMeta = $product->seoMeta()->create(['title' => 'Best Mango Juice']);
    $seoMeta->syncMedia('seo.media.image', UploadedFile::fake()->image('share.jpg', 1200, 630));
    SystemSetting::factory()->create()->seoMeta()->create(['description' => 'Site wide description']);

    $this->get(route('shop.product', $product->slug))
        ->assertOk()
        ->assertSee('Best Mango Juice - '.config('app.name'), false)
        ->assertSee('<meta property="og:title" content="Best Mango Juice" />', false)
        ->assertSee('<meta name="description" content="Site wide description" />', false)
        ->assertSee($seoMeta->fresh()->mediaUrl('seo.media.image', 'share'), false);
});

test('site seo saved in the backoffice overrides the env values', function () {
    config(['seo.description' => 'From env', 'seo.google_site_verification' => 'env-code']);
    SystemSetting::factory()->create(['google_site_verification' => 'db-code'])->seoMeta()->create(['title' => 'Shop Title', 'description' => 'From backoffice']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta name="description" content="From backoffice" />', false)
        ->assertSee('<meta property="og:title" content="Shop Title" />', false)
        ->assertSee('<meta name="google-site-verification" content="db-code" />', false);
});

test('the backoffice can hide the site but cannot show it when env turns indexing off', function () {
    SystemSetting::factory()->create(['seo_indexing_enabled' => false]);

    $this->get(route('home'))->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
    $this->get(route('sitemap'))->assertNotFound();

    SystemSetting::query()->update(['seo_indexing_enabled' => true]);
    FrontendCacheKeyEnum::SystemSettings->forget();
    config(['seo.indexing_enabled' => false]);

    $this->get(route('home'))->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
    $this->get(route('robots'))->assertSee("Disallow: /\n", false);
});

test('hide from search applies only when page noindex is enabled', function () {
    $product = Product::factory()->create();
    $product->seoMeta()->create(['noindex' => true]);

    $this->get(route('shop.product', $product->slug))->assertDontSee('noindex');
    $this->get(route('sitemap'))->assertSee(route('shop.product', $product->slug), false);

    config(['seo.page_noindex_enabled' => true]);

    $this->get(route('shop.product', $product->slug))->assertSee('<meta name="robots" content="noindex, nofollow" />', false);
    $this->get(route('sitemap'))->assertDontSee(route('shop.product', $product->slug), false);
});
