<?php

use App\Enums\Catalog\ProductBadgeEnum;
use App\Enums\Catalog\ProductStatusEnum;
use App\Livewire\Backoffice\Products\Index;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Backoffice\Catalog\CategoryService;
use App\Services\Backoffice\Catalog\ProductService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests cannot access product management', function () {
    $this->get(route('manage.products.index'))
        ->assertRedirect(route('login'));
});

test('regular users are redirected from product management', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('manage.products.index'))
        ->assertRedirect(route('user.dashboard'));
});

test('administrators can view the product management page', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('manage.products.index'))
        ->assertOk();
});

test('product service creates, updates and soft deletes a product', function () {
    $service = app(ProductService::class);

    $product = $service->create([
        'sku' => 'MOUSE-01',
        'name' => 'Wireless Mouse',
        'description' => 'A quiet wireless mouse',
        'price' => 450,
        'quantity' => 100,
        'status' => ProductStatusEnum::InStock->value,
        'badge' => ProductBadgeEnum::Hot->value,
    ]);

    expect($product->name)->toBe('Wireless Mouse')
        ->and($product->quantity)->toBe(100)
        ->and($product->type)->toBeNull()
        ->and($product->price)->toBe(450);

    $service->update($product->id, [
        'sku' => 'MOUSE-01',
        'name' => 'Wireless Mouse Pro',
        'price' => 500,
        'quantity' => 12,
        'status' => ProductStatusEnum::InStock->value,
    ]);

    $product->refresh();

    expect($product->name)->toBe('Wireless Mouse Pro')
        ->and($product->quantity)->toBe(12);

    $service->delete($product->id);

    expect(Product::find($product->id))->toBeNull()
        ->and(Product::withTrashed()->find($product->id))->not->toBeNull();

    $newProduct = $service->create([
        'sku' => 'MOUSE-02',
        'name' => 'Wireless Mouse Pro',
        'price' => 300,
        'quantity' => 1,
        'status' => ProductStatusEnum::InStock->value,
    ]);

    expect($newProduct->id)->not->toBe($product->id)
        ->and($newProduct->slug)->not->toBe($product->slug);
});

test('the product status follows the stock unless the product is archived', function () {
    $service = app(ProductService::class);

    $product = $service->create(['sku' => 'LAMP-01', 'name' => 'Desk Lamp', 'price' => 900, 'quantity' => 0, 'status' => ProductStatusEnum::InStock->value]);

    expect($product->status)->toBe(ProductStatusEnum::OutOfStock);

    $service->update($product->id, ['sku' => 'LAMP-01', 'name' => 'Desk Lamp', 'price' => 900, 'quantity' => 5, 'status' => ProductStatusEnum::OutOfStock->value]);

    expect($product->refresh()->status)->toBe(ProductStatusEnum::InStock);

    $service->update($product->id, ['sku' => 'LAMP-01', 'name' => 'Desk Lamp', 'price' => 900, 'quantity' => 5, 'status' => ProductStatusEnum::Archived->value]);

    expect($product->refresh()->status)->toBe(ProductStatusEnum::Archived);
});

test('product service supports searching, filtering by status and category', function () {
    $service = app(ProductService::class);
    $parent = Category::factory()->create();
    $child = Category::factory()->childOf($parent)->create();

    Product::factory()->create(['name' => 'PHP 8.4 Deep Dive', 'category_id' => $child->id]);
    Product::factory()->archived()->create(['name' => 'Advanced Algorithms']);

    expect($service->getProducts(search: 'PHP')->total())->toBe(1)
        ->and($service->getProducts(status: ProductStatusEnum::InStock)->total())->toBe(1)
        ->and($service->getProducts(categoryId: $parent->id)->items()[0]->name)->toBe('PHP 8.4 Deep Dive');
});

test('a product category path runs from the root down to the chosen category', function () {
    $parent = Category::factory()->create(['name' => 'Crops']);
    $child = Category::factory()->childOf($parent)->create(['name' => 'Rice']);

    expect(app(CategoryService::class)->getCategoryLevelIds($child->id))->toBe([$parent->id, $child->id])
        ->and(app(CategoryService::class)->getCategoryLevelIds(null))->toBe([])
        ->and(app(CategoryService::class)->getCategoryLabel($child->id))->toBe('Rice > Crops')
        ->and(app(CategoryService::class)->getCategoryLabel(null))->toBeNull();

    $product = Product::factory()->create(['category_id' => $child->id]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewProduct', $product->id)
        ->assertSee('Rice &gt; Crops', false);
});

test('the product category filter loads root categories five at a time and shows sub-categories of the chosen root', function () {
    $roots = collect(range(1, 7))->map(fn (int $i) => Category::factory()->create(['name' => 'Root '.$i]));
    $children = collect(range(1, 6))->map(fn (int $i) => Category::factory()->childOf($roots->first())->create(['name' => 'Sub '.$i]));
    $rootProduct = Product::factory()->create(['name' => 'Root Product', 'category_id' => $roots->first()->id]);
    $subProduct = Product::factory()->create(['name' => 'Sub Product', 'category_id' => $children->first()->id]);
    Product::factory()->create(['name' => 'Other Product', 'category_id' => $roots->last()->id]);

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->assertSet('categoryFilterOptions.0.has_more', true)
        ->assertSeeHtml('value="__load_more__"')
        ->assertCount('categoryFilterOptions.0.options', 5)
        ->call('loadMoreCategoryLevel', 'categoryFilter', 0)
        ->assertCount('categoryFilterOptions.0.options', 7)
        ->assertSet('categoryFilterOptions.0.has_more', false)
        ->assertSet('categoryFilterOptions.1.disabled', true)
        ->set('categoryFilter.0', (string) $roots->first()->id)
        ->assertCount('categoryFilterOptions.1.options', 5)
        ->assertSet('categoryFilterOptions.1.has_more', true);

    expect($component->get('products')->pluck('id')->sort()->values()->all())->toBe(collect([$rootProduct->id, $subProduct->id])->sort()->values()->all());

    $component->set('categoryFilter.1', (string) $children->first()->id);

    expect($component->get('products')->pluck('id')->all())->toBe([$subProduct->id]);

    $component->set('categoryFilter.0', (string) $roots->last()->id)->assertSet('categoryFilter', [(string) $roots->last()->id]);
});

test('administrators can create, update, and delete products via livewire', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $category = Category::factory()->create();

    Livewire::test(Index::class)
        ->set('name', 'Mechanical Keyboard')
        ->set('sku', 'KB-MECH-01')
        ->set('price', '650')
        ->set('quantity', '8')
        ->set('discountPercentage', '10')
        ->set('categoryLevels', [(string) $category->id])
        ->set('productStatus', ProductStatusEnum::InStock->value)
        ->call('saveProduct')
        ->assertHasNoErrors();

    $product = Product::query()->where('name', 'Mechanical Keyboard')->firstOrFail();

    expect($product->sku)->toBe('KB-MECH-01')
        ->and($product->quantity)->toBe(8)
        ->and($product->category_id)->toBe($category->id)
        ->and($product->final_price)->toBe(585);

    Livewire::test(Index::class)
        ->call('editProduct', $product->id)
        ->set('quantity', '20')
        ->call('saveProduct')
        ->assertHasNoErrors();

    expect($product->refresh()->quantity)->toBe(20);

    Livewire::test(Index::class)
        ->call('confirmDeleteProduct', $product->id)
        ->call('deleteProduct');

    expect(Product::find($product->id))->toBeNull();
});

test('a product needs a category and a stock quantity', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->set('name', 'Uncategorised Product')
        ->set('price', '100')
        ->call('saveProduct')
        ->assertHasErrors(['sku' => 'required', 'categoryLevels.0' => 'required', 'quantity' => 'required'])
        ->assertSee('The category field is required.');

    expect(Product::where('name', 'Uncategorised Product')->exists())->toBeFalse();
});

test('with three levels a product can sit in a sub-sub-category and the root filter finds it', function () {
    config(['app.category_level' => 3]);
    $root = Category::factory()->create();
    $sub = Category::factory()->childOf($root)->create();
    $leaf = Category::factory()->childOf($sub)->create();

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createProduct')
        ->assertSeeHtml('wire:model.live="categoryLevels.0"')
        ->assertCount('categoryLevelOptions', 3)
        ->assertSet('categoryLevelOptions.1.disabled', true)
        ->assertSet('categoryLevelOptions.2.disabled', true)
        ->set('categoryLevels.0', (string) $root->id)
        ->assertSet('categoryLevelOptions.1.disabled', false)
        ->set('categoryLevels.1', (string) $sub->id)
        ->assertSet('categoryLevelOptions.2.disabled', false)
        ->set('categoryLevels.2', (string) $leaf->id)
        ->set('name', 'Deep Product')
        ->set('sku', 'DEEP-01')
        ->set('price', '100')
        ->set('quantity', '3')
        ->call('saveProduct')
        ->assertHasNoErrors();

    $product = Product::firstWhere('name', 'Deep Product');
    expect($product->category_id)->toBe($leaf->id);

    $component->call('editProduct', $product->id)->assertSet('categoryLevels', [(string) $root->id, (string) $sub->id, (string) $leaf->id]);

    expect(app(ProductService::class)->getProducts(categoryId: $root->id)->pluck('id')->all())->toBe([$product->id]);

    config(['app.category_level' => 2]);

    expect(app(ProductService::class)->getProducts(categoryId: $root->id)->total())->toBe(0);
});

test('a product sku must be unique', function () {
    $product = Product::factory()->create(['sku' => 'DUP-01']);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->set('sku', 'DUP-01')
        ->call('saveProduct')
        ->assertHasErrors(['sku' => 'unique'])
        ->call('editProduct', $product->id)
        ->call('saveProduct')
        ->assertHasNoErrors('sku');
});

test('a product category must stay within the configured level', function () {
    $root = Category::factory()->create();
    $sub = Category::factory()->childOf($root)->create();
    $leaf = Category::factory()->childOf($sub)->create();

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->set('name', 'Too Deep Product')
        ->set('price', '100')
        ->set('categoryLevels', [(string) $root->id, (string) $sub->id, (string) $leaf->id])
        ->call('saveProduct')
        ->assertHasErrors('categoryLevels.2');
});

test('administrators can feature and unfeature a product', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $product = Product::factory()->create(['category_id' => Category::factory()]);

    Livewire::test(Index::class)
        ->call('editProduct', $product->id)
        ->set('isFeatured', true)
        ->call('saveProduct')
        ->assertHasNoErrors();

    expect($product->refresh()->is_featured)->toBeTrue();

    Livewire::test(Index::class)
        ->call('editProduct', $product->id)
        ->assertSet('isFeatured', true)
        ->set('isFeatured', false)
        ->call('saveProduct')
        ->assertHasNoErrors();

    expect($product->refresh()->is_featured)->toBeFalse();
});

test('the product table renders products and manages modal states', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $product = Product::factory()->create([
        'name' => 'Domain-Driven Design',
        'price' => 55,
        'discount_percentage' => 15,
        'badge' => ProductBadgeEnum::Hot->value,
    ]);

    Livewire::test(Index::class)
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->assertSee('Domain-Driven Design')
        ->assertSee('In Stock')
        ->call('createProduct')
        ->assertSet('showForm', true)
        ->assertSet('name', '')
        ->call('editProduct', $product->id)
        ->assertSet('editingProductId', $product->id)
        ->assertSet('name', 'Domain-Driven Design')
        ->set('showForm', false)
        ->assertSet('editingProductId', null)
        ->call('viewProduct', $product->id)
        ->assertSet('showDetails', true)
        ->assertSet('viewingProductId', $product->id)
        ->assertSee('Domain-Driven Design');
});

test('product descriptions are saved as sanitized rich text', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->set('name', 'Rich Text Product')
        ->set('sku', 'RICH-01')
        ->set('categoryLevels', [(string) Category::factory()->create()->id])
        ->set('price', '100')
        ->set('quantity', '1')
        ->set('description', '<div><strong onclick="alert(1)">Bold</strong> <a href="javascript:alert(1)">bad</a> <a href="https://example.com">good</a><script>alert(1)</script><img src="x" onerror="alert(1)"></div>')
        ->call('saveProduct')
        ->assertHasNoErrors();

    expect(Product::where('name', 'Rich Text Product')->value('description'))
        ->toBe('<div><strong>Bold</strong> <a>bad</a> <a href="https://example.com">good</a></div>');
});

test('empty rich text descriptions are stored as null', function () {
    expect(sanitizeRichText('<div><br></div>'))->toBeNull()
        ->and(sanitizeRichText(null))->toBeNull();
});

test('a product keeps up to five images and the first one is the thumbnail', function () {
    Storage::fake('public');
    $product = Product::factory()->create();

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('uploadMedia', $product->id)
        ->set('imageUploads', collect(range(1, 3))->map(fn (int $i) => UploadedFile::fake()->image("photo-{$i}.jpg"))->all())
        ->call('saveMedia')
        ->assertHasNoErrors();

    $media = $product->refresh()->configuredMediaItems('product.media.images');

    expect($media)->toHaveCount(3)
        ->and($product->thumbnail_url)->toBe($product->mediaItemUrl($media->first(), 'small'))
        ->and($product->gallery_images)->toHaveCount(3);

    $component->call('uploadMedia', $product->id)
        ->call('chooseThumbnail', 'media-'.$media->last()->id)
        ->call('toggleRemoveImage', $media->first()->id)
        ->call('saveMedia')
        ->assertHasNoErrors();

    $media = $product->refresh()->configuredMediaItems('product.media.images');

    expect($media)->toHaveCount(2)
        ->and($media->first()->file_name)->toContain('.jpg')
        ->and($product->thumbnail_url)->toBe($product->mediaItemUrl($media->first(), 'small'))
        ->and(app(ProductService::class)->imageGallery($product->id)['items'][0]['is_thumbnail'])->toBeTrue();

    $component->call('viewMedia', $product->id)
        ->assertSet('showMediaPreview', true)
        ->assertSee($product->mediaItemUrl($media->last(), 'large'));
});

test('the image preview saves a new thumbnail right away', function () {
    Storage::fake('public');
    $product = Product::factory()->create();
    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('uploadMedia', $product->id)
        ->set('imageUploads', collect(range(1, 3))->map(fn (int $i) => UploadedFile::fake()->image("photo-{$i}.jpg"))->all())
        ->call('saveMedia');
    $last = $product->refresh()->configuredMediaItems('product.media.images')->last();

    $component->call('viewMedia', $product->id)
        ->assertSee(__('Set as Thumbnail'))
        ->call('setPreviewThumbnail', $last->id)
        ->assertHasNoErrors();

    expect($product->refresh()->configuredMediaItems('product.media.images')->pluck('id')->first())->toBe($last->id)
        ->and($product->gallery_images[0]['id'])->toBe($last->id);
});

test('a pending product image can be selected as the thumbnail', function () {
    Storage::fake('public');
    $product = Product::factory()->create();

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('uploadMedia', $product->id)
        ->set('imageUploads', [
            UploadedFile::fake()->image('first.jpg'),
            UploadedFile::fake()->image('second.jpg'),
        ])
        ->call('chooseThumbnail', 'upload-1')
        ->assertSet('imageOrder', ['upload-1', 'upload-0'])
        ->call('saveMedia')
        ->assertHasNoErrors();

    $media = $product->refresh()->configuredMediaItems('product.media.images');

    expect($media->first()->id)->toBe($media->sortBy('id')->last()->id);
});

test('product images can be reordered together with pending uploads', function () {
    Storage::fake('public');
    $product = Product::factory()->create();
    $first = $product->attachMedia('product.media.images', UploadedFile::fake()->image('first.jpg'));
    $second = $product->attachMedia('product.media.images', UploadedFile::fake()->image('second.jpg'));

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('uploadMedia', $product->id)
        ->set('imageUploads', [UploadedFile::fake()->image('third.jpg')])
        ->call('moveImage', 'upload-0', -1)
        ->call('moveImage', 'upload-0', -1)
        ->call('moveImage', 'media-'.$first->id, 1)
        ->assertSet('imageOrder', ['upload-0', 'media-'.$second->id, 'media-'.$first->id])
        ->call('saveMedia')
        ->assertHasNoErrors();

    $ids = $product->refresh()->configuredMediaItems('product.media.images')->pluck('id')->all();

    expect($ids)->toHaveCount(3)
        ->and(array_slice($ids, 1))->toBe([$second->id, $first->id])
        ->and($ids[0])->not->toBeIn([$first->id, $second->id]);
});

test('a product cannot have more than five images', function () {
    Storage::fake('public');
    $product = Product::factory()->create();
    $product->attachMedia('product.media.images', UploadedFile::fake()->image('first.jpg'));

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('uploadMedia', $product->id)
        ->set('imageUploads', collect(range(1, 5))->map(fn (int $i) => UploadedFile::fake()->image("photo-{$i}.jpg"))->all())
        ->call('saveMedia')
        ->assertHasErrors('imageUploads');

    expect($product->refresh()->configuredMediaItems('product.media.images'))->toHaveCount(1);
});

test('staff without the products permission cannot open product management', function () {
    $this->actingAs(User::factory()->editor()->create())
        ->get(route('manage.products.index'))
        ->assertRedirect(route('dashboard'));
});
