<?php

use App\Livewire\Frontend\Shop\Index;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use App\Services\Backoffice\Catalog\CategoryService;
use App\Services\Frontend\Shop\PageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('the landing page shows featured products, featured categories and the live offer', function () {
    Product::factory()->featured()->create(['name' => 'Featured Rice Cooker']);
    Category::factory()->featured()->create(['name' => 'Fisheries']);
    Offer::factory()->create(['name' => 'Eid Offer', 'discount_percentage' => 15]);
    Offer::factory()->inactive()->create(['name' => 'Old Offer']);
    Banner::factory()->create(['title' => 'Banner Headline']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee(['Featured Rice Cooker', 'Fisheries', 'Eid Offer', 'Banner Headline'])
        ->assertDontSee('Old Offer');
});

test('the landing offer shows its headline', function () {
    Offer::factory()->create(['headline' => 'Eid gifts at half the worry']);

    $this->get(route('home'))->assertSee('Eid gifts at half the worry');
});

test('the countdown shows the time left to the second, ticks unless static and shows its expired slot once the date passes', function () {
    $this->travelTo(now()->startOfMinute());

    expect(Blade::render('<x-frontend.content.countdown :to="$to" />', ['to' => now()->addDays(3)->addHours(4)->addSeconds(5)]))
        ->toContain(__('Ends in'))->toContain('03d : 04h : 00m : 05s')->toContain('x-data="countdown(')
        ->and(Blade::render('<x-frontend.content.countdown :to="$to" :live="false" />', ['to' => now()->addHour()]))
        ->not->toContain('x-data')
        ->and(Blade::render('<x-frontend.content.countdown :to="$to">Sale ends<x-slot:expired>Ended</x-slot:expired></x-frontend.content.countdown>', ['to' => now()->subMinute()]))
        ->toContain('Ended')->not->toContain('Sale ends')
        ->and(Blade::render('<x-frontend.content.countdown :to="$to">Sale ends</x-frontend.content.countdown>', ['to' => now()->addHour()]))
        ->toContain('Sale ends')->toContain('01h : 00m : 00s')->not->toContain('00d')->not->toContain(__('Ends in'));
});

test('the shop filters by parent category including its sub-categories and hides archived products', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->childOf($parent)->create();
    Product::factory()->create(['name' => 'Child Category Product', 'category_id' => $child->id]);
    Product::factory()->create(['name' => 'Other Product']);
    Product::factory()->archived()->create(['name' => 'Archived Product', 'category_id' => $child->id]);

    Livewire::test(Index::class)
        ->set('categoryIds', [$parent->id])
        ->assertSee('Child Category Product')
        ->assertDontSee('Other Product')
        ->assertDontSee('Archived Product');
});

test('the shop searches by product name', function () {
    Product::factory()->create(['name' => 'Fish Tank']);
    Product::factory()->create(['name' => 'Rice Cooker']);

    Livewire::test(Index::class)
        ->set('search', 'Tank')
        ->assertSee('Fish Tank')
        ->assertDontSee('Rice Cooker');
});

test('the product page renders its image gallery and archived products return 404', function () {
    $product = Product::factory()->create(['name' => 'Mango Juice']);
    $archived = Product::factory()->archived()->create();

    Storage::fake('public');
    $product->attachMedia('product.media.images', UploadedFile::fake()->image('front.jpg'));
    $product->attachMedia('product.media.images', UploadedFile::fake()->image('back.jpg'));

    $this->get(route('shop.product', $product->slug))
        ->assertOk()
        ->assertSee('Mango Juice')
        ->assertSee(__('Show image :number', ['number' => 2]))
        ->assertSee($product->refresh()->gallery_images[1]['thumbnail'], false);
    $this->get(route('shop.product', $archived->slug))->assertNotFound();
});

test('customers see their dashboard and orders', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('user.dashboard'))->assertOk();
    $this->get(route('user.orders'))->assertOk();
    $this->get(route('checkout'))->assertOk();
});

test('the shop filters products by the price customers pay', function () {
    Product::factory()->create(['name' => 'Cheap Product', 'price' => 100]);
    Product::factory()->create(['name' => 'Discounted Product', 'price' => 400, 'discount_percentage' => 50]);
    Product::factory()->create(['name' => 'Pricey Product', 'price' => 900]);

    Livewire::test(Index::class)
        ->set('minPrice', '150')
        ->set('maxPrice', '300')
        ->assertSee('Discounted Product')
        ->assertDontSee(['Cheap Product', 'Pricey Product'])
        ->call('clearFilters')
        ->assertSee(['Cheap Product', 'Discounted Product', 'Pricey Product']);
});

test('the customer dashboard shows the three newest products', function () {
    Product::factory()->create(['name' => 'Oldest Product', 'created_at' => now()->subDays(4)]);
    Product::factory()->create(['name' => 'Third Product', 'created_at' => now()->subDays(3)]);
    Product::factory()->create(['name' => 'Second Product', 'created_at' => now()->subDays(2)]);
    Product::factory()->create(['name' => 'Newest Product', 'created_at' => now()->subDay()]);

    $this->actingAs(User::factory()->create())
        ->get(route('user.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Recently added products', 'Newest Product', 'Second Product', 'Third Product'])
        ->assertDontSee('Oldest Product');
});

test('staff are sent to the admin dashboard from customer pages', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->get(route('user.dashboard'))
        ->assertRedirect(route('dashboard'));
});

test('the shop loads root categories with their sub-categories in small batches', function () {
    $roots = Category::factory()->count(12)->sequence(fn ($sequence) => ['name' => sprintf('Root %02d', $sequence->index + 1), 'sort_order' => $sequence->index + 1])->create();
    Category::factory()->childOf($roots->first())->create(['name' => 'First Child']);
    Category::factory()->childOf($roots->last())->create(['name' => 'Last Child']);

    $component = Livewire::test(Index::class)
        ->assertSee(['Root 01', 'First Child'])
        ->assertDontSee(['Root 12', 'Last Child'])
        ->assertSet('categories.has_more', true);

    while ($component->get('categories')['has_more']) {
        $component->call('loadMoreCategories');
    }

    $component->assertSee(['Root 12', 'Last Child']);
});

test('ticking a category ticks its sub-categories and unticking one keeps the parent and the rest', function () {
    $parent = Category::factory()->create();
    $kept = Category::factory()->childOf($parent)->create();
    $dropped = Category::factory()->childOf($parent)->create();
    Product::factory()->create(['name' => 'Parent Product', 'category_id' => $parent->id]);
    Product::factory()->create(['name' => 'Kept Product', 'category_id' => $kept->id]);
    Product::factory()->create(['name' => 'Dropped Product', 'category_id' => $dropped->id]);

    $component = Livewire::test(Index::class)->set('categoryIds', [$parent->id]);

    expect($component->get('categoryIds'))->toEqualCanonicalizing([$parent->id, $kept->id, $dropped->id]);

    $component->set('categoryIds', [$parent->id, $kept->id])
        ->assertSet('categoryIds', [$parent->id, $kept->id])
        ->assertSee(['Parent Product', 'Kept Product'])
        ->assertDontSee('Dropped Product')
        ->set('categoryIds', [$kept->id])
        ->assertSet('categoryIds', []);
});

test('a category link ticks its sub-categories but a saved selection is kept as it is', function () {
    $parent = Category::factory()->create();
    $kept = Category::factory()->childOf($parent)->create();
    $dropped = Category::factory()->childOf($parent)->create();

    expect(Livewire::withQueryParams(['category' => (string) $parent->id])->test(Index::class)->get('categoryIds'))
        ->toEqualCanonicalizing([$parent->id, $kept->id, $dropped->id]);

    Livewire::withQueryParams(['category' => $parent->id.','.$kept->id])
        ->test(Index::class)
        ->assertSet('categoryIds', [$parent->id, $kept->id]);
});

test('the category filter is kept in the url as one comma separated value', function () {
    $first = Category::factory()->create();
    $second = Category::factory()->create();
    Product::factory()->create(['name' => 'First Category Product', 'category_id' => $first->id]);
    Product::factory()->create(['name' => 'Third Product']);

    Livewire::withQueryParams(['category' => (string) $first->id])
        ->test(Index::class)
        ->assertSet('categoryIds', [$first->id])
        ->assertSee('First Category Product')
        ->assertDontSee('Third Product')
        ->set('categoryIds', [$first->id, $second->id])
        ->assertSet('categoryFilter', $first->id.','.$second->id)
        ->set('categoryIds', [])
        ->assertSet('categoryFilter', '');

    Livewire::withQueryParams(['category' => '['.$first->id.','.$second->id.']'])
        ->test(Index::class)
        ->assertSet('categoryIds', [$first->id, $second->id]);
});

test('with three levels the shop nests categories, filters a root down to its sub-sub-categories and shows the full breadcrumb', function () {
    config(['app.category_level' => 3]);
    $root = Category::factory()->create(['name' => 'Crops']);
    $sub = Category::factory()->childOf($root)->create(['name' => 'Grains']);
    $leaf = Category::factory()->childOf($sub)->create(['name' => 'Rice']);
    $product = Product::factory()->create(['name' => 'Rice Farming', 'category_id' => $leaf->id]);
    Product::factory()->create(['name' => 'Unrelated Product']);

    Livewire::withQueryParams(['category' => (string) $root->id])
        ->test(Index::class)
        ->assertSee(['Crops', 'Grains', 'Rice', 'Rice Farming'])
        ->assertDontSee('Unrelated Product');

    $this->get(route('shop.product', $product->slug))
        ->assertOk()
        ->assertSeeInOrder(['Crops', 'Grains', 'Rice', 'Rice Farming']);

    expect($product->load('category.parent.parent')->aiAssistantEmbeddingText())->toContain('Crops › Grains › Rice');
});

test('lowering the category level hides deeper categories from the shop', function () {
    config(['app.category_level' => 3]);
    $root = Category::factory()->create(['name' => 'Crops']);
    $sub = Category::factory()->childOf($root)->create(['name' => 'Grains']);
    $leaf = Category::factory()->childOf($sub)->create(['name' => 'Paddy']);
    Product::factory()->create(['name' => 'Paddy Product', 'category_id' => $leaf->id]);
    config(['app.category_level' => 2]);

    Livewire::withQueryParams(['category' => (string) $root->id])
        ->test(Index::class)
        ->assertSee('Grains')
        ->assertDontSee(['Paddy', 'Paddy Product']);
});

test('shop category batches and product pages are cached until an admin changes them', function () {
    $service = app(PageService::class);
    $root = Category::factory()->create(['name' => 'Crops']);
    Product::factory()->create(['name' => 'Cached Product', 'category_id' => $root->id]);

    $service->getCategoryTreeSlice(2);
    $service->searchProducts(categoryIds: [$root->id], page: 1);

    DB::enableQueryLog();
    $categories = $service->getCategoryTreeSlice(2);
    $products = $service->searchProducts(categoryIds: [$root->id], page: 1);
    expect(DB::getQueryLog())->toBe([])
        ->and($categories['items'][0]['name'])->toBe('Crops')
        ->and($products->pluck('name')->all())->toBe(['Cached Product'])
        ->and($products->total())->toBe(1);

    Product::factory()->create(['name' => 'New Product', 'category_id' => $root->id]);
    app(CategoryService::class)->update($root->id, ['name' => 'Field Crops']);

    expect($service->getCategoryTreeSlice(2)['items'][0]['name'])->toBe('Field Crops')
        ->and($service->searchProducts(categoryIds: [$root->id], page: 1)->total())->toBe(2);
});

test('the loaded category count is kept when the customer leaves the shop and comes back', function () {
    Category::factory()->count(6)->create();

    $loaded = Livewire::test(Index::class)->call('loadMoreCategories')->get('categoryLimit');

    expect(Livewire::test(Index::class)->get('categoryLimit'))->toBe($loaded);
});

test('the shop paginates products and shows the summary under the pages', function () {
    Product::factory()->count(14)->create();

    Livewire::test(Index::class)
        ->assertSee(__('Showing :from to :to of :total products', ['from' => 1, 'to' => 12, 'total' => 14]))
        ->call('setPage', 2)
        ->assertSee(__('Showing :from to :to of :total products', ['from' => 13, 'to' => 14, 'total' => 14]));
});
