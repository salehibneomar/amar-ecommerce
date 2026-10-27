<?php

use App\Enums\User\PermissionEnum;
use App\Livewire\Backoffice\Categories\Index as CategoriesIndex;
use App\Livewire\Backoffice\Products\Index as ProductsIndex;
use App\Livewire\Backoffice\SeoMetas\Index;
use App\Models\Category;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('staff with the seo permission save the site defaults', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->set('indexingEnabled', false)
        ->set('googleSiteVerification', ' abc-123 ')
        ->set('seoMetaTitle', 'Fresh Groceries Delivered')
        ->set('seoMetaDescription', 'Order fresh groceries online.')
        ->set('seoMetaImage', UploadedFile::fake()->image('share.jpg', 1200, 630))
        ->call('save')
        ->assertHasNoErrors();

    $setting = SystemSetting::query()->sole();

    expect($setting->seo_indexing_enabled)->toBeFalse()
        ->and($setting->google_site_verification)->toBe('abc-123')
        ->and($setting->seoMeta->title)->toBe('Fresh Groceries Delivered')
        ->and($setting->seoMeta->description)->toBe('Order fresh groceries online.')
        ->and($setting->seoMeta->hasConfiguredMedia('seo.media.image'))->toBeTrue();
});

test('seo titles and descriptions over the search engine limits are rejected', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->set('seoMetaTitle', str_repeat('a', 71))
        ->set('seoMetaDescription', str_repeat('a', 161))
        ->call('save')
        ->assertHasErrors(['seoMetaTitle' => 'max', 'seoMetaDescription' => 'max']);

    expect(SystemSetting::query()->first()?->seoMeta)->toBeNull();
});

test('staff need the seo permission to open the seo page', function () {
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($moderator)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('manage.seo-metas.index'))
        ->assertRedirect(route('dashboard'));

    $moderator->givePermissionTo(PermissionEnum::ManageSeoMeta->value);

    $this->actingAs($moderator)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('manage.seo-metas.index'))
        ->assertOk();
});

test('the product screen saves seo for a product', function () {
    $product = Product::factory()->create();
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(ProductsIndex::class)
        ->call('editSeoMeta', $product->id)
        ->assertSet('showSeoMetaForm', true)
        ->assertDontSee(__('Share Image'))
        ->set('seoMetaTitle', 'Best Mango Juice')
        ->set('seoMetaDescription', 'Cold pressed and fresh.')
        ->call('saveSeoMeta')
        ->assertHasNoErrors()
        ->assertSet('showSeoMetaForm', false);

    expect($product->seoMeta()->sole())
        ->title->toBe('Best Mango Juice')
        ->description->toBe('Cold pressed and fresh.');
});

test('the category screen loads and updates the saved seo', function () {
    $category = Category::factory()->create();
    $category->seoMeta()->create(['title' => 'Old Title']);
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(CategoriesIndex::class)
        ->call('editSeoMeta', $category->id)
        ->assertSet('seoMetaTitle', 'Old Title')
        ->assertDontSee(__('Share Image'))
        ->set('seoMetaTitle', '')
        ->call('saveSeoMeta')
        ->assertHasNoErrors();

    expect($category->seoMeta()->sole()->title)->toBeNull();
});
