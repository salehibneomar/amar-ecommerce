<?php

use App\Enums\User\PermissionEnum;
use App\Livewire\Backoffice\Categories\Index;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use App\Services\Backoffice\Catalog\CategoryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('moderators without the permission cannot open categories', function () {
    $moderator = User::factory()->moderator()->create();

    $this->actingAs($moderator)->get(route('manage.categories.index'))->assertRedirect(route('dashboard'));

    $moderator->givePermissionTo(PermissionEnum::ManageCategories->value);

    $this->actingAs($moderator->refresh())->get(route('manage.categories.index'))->assertOk();
});

test('administrators create a parent and a sub-category', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->call('createCategory')
        ->set('name', 'কৃষি')
        ->call('saveCategory')
        ->assertHasNoErrors();

    $parent = Category::sole();

    Livewire::test(Index::class)
        ->call('createCategory')
        ->set('name', 'ধান চাষ')
        ->set('parentLevels', [(string) $parent->id])
        ->call('saveCategory')
        ->assertHasNoErrors();

    expect($parent->children()->pluck('name')->all())->toBe(['ধান চাষ'])
        ->and($parent->slug)->not->toBe('');
});

test('categories are limited to the configured level', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $parent = Category::factory()->create();
    $child = Category::factory()->childOf($parent)->create();

    Livewire::test(Index::class)
        ->set('name', 'Too deep')
        ->set('parentLevels', [(string) $parent->id, (string) $child->id])
        ->call('saveCategory')
        ->assertHasErrors('parentLevels.0');

    Livewire::test(Index::class)
        ->call('editCategory', $parent->id)
        ->set('parentLevels', [(string) Category::factory()->create()->id])
        ->call('saveCategory')
        ->assertHasErrors('parentLevels.0');
});

test('with three levels categories nest three deep and moving a branch updates its depth', function () {
    config(['app.category_level' => 3]);
    $this->actingAs(User::factory()->superAdmin()->create());
    $root = Category::factory()->create();
    $sub = Category::factory()->childOf($root)->create();

    Livewire::test(Index::class)
        ->call('createCategory')
        ->assertCount('parentLevelOptions', 2)
        ->assertSet('parentLevelOptions.1.disabled', true)
        ->set('parentLevels.0', (string) $root->id)
        ->assertSet('parentLevelOptions.1.disabled', false)
        ->set('parentLevels.1', (string) $sub->id)
        ->set('name', 'Leaf')
        ->call('saveCategory')
        ->assertHasNoErrors();

    $leaf = Category::firstWhere('name', 'Leaf');
    expect($leaf->parent_id)->toBe($sub->id)->and($leaf->depth)->toBe(3);

    Livewire::test(Index::class)
        ->call('editCategory', $leaf->id)
        ->assertSet('parentLevels', [(string) $root->id, (string) $sub->id]);

    Livewire::test(Index::class)
        ->call('editCategory', $root->id)
        ->set('parentLevels', [(string) $root->id, (string) $sub->id])
        ->call('saveCategory')
        ->assertHasErrors('parentLevels.0');

    $otherRoot = Category::factory()->create();

    Livewire::test(Index::class)
        ->call('editCategory', $sub->id)
        ->set('parentLevels', [(string) $otherRoot->id])
        ->call('saveCategory')
        ->assertHasNoErrors();

    expect($sub->refresh()->depth)->toBe(2)->and($leaf->refresh()->depth)->toBe(3);

    Livewire::test(Index::class)
        ->call('editCategory', $sub->id)
        ->set('parentLevels', [])
        ->call('saveCategory')
        ->assertHasNoErrors();

    expect($sub->refresh()->depth)->toBe(1)->and($leaf->refresh()->depth)->toBe(2);
});

test('with one level there is no parent choice', function () {
    config(['app.category_level' => 1]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createCategory')
        ->assertCount('parentLevelOptions', 0)
        ->assertCount('categoryFilterOptions', 0);
});

test('a parent with sub-categories cannot be deleted and deleting a category uncategorises its books', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $parent = Category::factory()->create();
    $child = Category::factory()->childOf($parent)->create();
    $book = Book::factory()->create(['category_id' => $child->id]);

    Livewire::test(Index::class)
        ->call('confirmDeleteCategory', $parent->id)
        ->call('deleteCategory')
        ->assertDispatched('toast-show');

    expect(Category::find($parent->id))->not->toBeNull();

    Livewire::test(Index::class)
        ->call('confirmDeleteCategory', $child->id)
        ->call('deleteCategory');

    expect(Category::find($child->id))->toBeNull()
        ->and($book->refresh()->category_id)->toBeNull();
});

test('the sub-category filter loads root categories five at a time', function () {
    $roots = collect(range(1, 7))->map(fn (int $i) => Category::factory()->create(['name' => "Root {$i}"]));
    Category::factory()->childOf($roots->first())->create(['name' => 'Child']);

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class);

    expect($component->get('categoryFilterOptions')[0])->toBe([
        'options' => $roots->take(5)->pluck('name', 'id')->all(),
        'has_more' => true,
        'disabled' => false,
    ]);

    $component->call('loadMoreCategoryLevel', 'categoryFilter', 0);

    expect($component->get('categoryFilterOptions')[0])->toBe([
        'options' => $roots->pluck('name', 'id')->all(),
        'has_more' => false,
        'disabled' => false,
    ]);
});

test('the selected sub-category filter stays listed beyond the loaded page', function () {
    $roots = collect(range(1, 7))->map(fn (int $i) => Category::factory()->create(['name' => "Root {$i}"]));

    $options = app(CategoryService::class)->getCategoryOptionsBatch(5, $roots->last()->id)['options'];

    expect($options)->toHaveCount(6)->toHaveKey($roots->last()->id);
});

test('the parent category choice loads ten at a time, skips the edited category and keeps the current parent', function () {
    $roots = collect(range(1, 12))->map(fn (int $i) => Category::factory()->create(['name' => sprintf('Root %02d', $i)]));
    $child = Category::factory()->childOf($roots->last())->create();

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createCategory')
        ->assertSee(__('Load more…'));

    expect($component->get('parentLevelOptions')[0])->toBe([
        'options' => $roots->take(10)->pluck('name', 'id')->all(),
        'has_more' => true,
        'disabled' => false,
    ]);

    $component->call('loadMoreCategoryLevel', 'parentLevels', 0);
    expect($component->get('parentLevelOptions')[0]['options'])->toHaveCount(12)
        ->and($component->get('parentLevelOptions')[0]['has_more'])->toBeFalse();

    $component->call('editCategory', $child->id);
    expect($component->get('parentLevelOptions')[0]['options'])->toHaveCount(11)->toHaveKey($roots->last()->id);

    $component->call('editCategory', $roots->first()->id);
    expect($component->get('parentLevelOptions')[0]['options'])->not->toHaveKey($roots->first()->id);
});

test('categories can be filtered to root categories only', function () {
    $root = Category::factory()->create(['name' => 'Root']);
    $child = Category::factory()->childOf($root)->create(['name' => 'Child']);

    expect(collect(app(CategoryService::class)->getCategories(rootOnly: true)->items())->pluck('id')->all())->toBe([$root->id]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->set('categoryFilter.0', (string) $root->id)
        ->set('rootOnly', true)
        ->assertSet('categoryFilter', [])
        ->set('categoryFilter.0', (string) $root->id)
        ->assertSet('rootOnly', false)
        ->assertSee($child->name);
});

test('administrators upload a square category image from the row actions, shown small in the table, regular on the home page and large in the media view', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->superAdmin()->create());
    $category = Category::factory()->create(['name' => 'Crops', 'is_featured' => true]);

    Livewire::test(Index::class)
        ->assertDontSeeHtml('wire:click="viewMedia('.$category->id.')"')
        ->call('uploadMedia', $category->id)
        ->assertSet('showMediaUpload', true)
        ->set('imageUpload', UploadedFile::fake()->image('crops.jpg', 1200, 800))
        ->call('saveMedia')
        ->assertHasNoErrors()
        ->assertSet('showMediaUpload', false);

    $media = $category->refresh()->configuredMedia('category.media.image');

    expect($media)->not->toBeNull()
        ->and($media->hasGeneratedConversion('small'))->toBeTrue()
        ->and($media->hasGeneratedConversion('regular'))->toBeTrue()
        ->and(array_slice(getimagesize($media->getPath('large')) ?: [], 0, 2))->toBe([600, 600]);

    Livewire::test(Index::class)
        ->assertSeeHtml($category->image_thumbnail_url)
        ->call('viewMedia', $category->id)
        ->assertSet('showMediaPreview', true)
        ->call('viewImage')
        ->assertSet('showImageViewer', true)
        ->assertSeeHtml($category->mediaUrl('category.media.image', 'large'));

    $this->get(route('home'))->assertSee($category->mediaUrl('category.media.image', 'regular'), false);

    Livewire::test(Index::class)
        ->call('uploadMedia', $category->id)
        ->set('removeImage', true)
        ->call('saveMedia')
        ->assertHasNoErrors();

    expect($category->refresh()->hasConfiguredMedia('category.media.image'))->toBeFalse();
});

test('a category image must be an image file', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->superAdmin()->create());
    $category = Category::factory()->create();

    Livewire::test(Index::class)
        ->call('uploadMedia', $category->id)
        ->set('imageUpload', UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'))
        ->call('saveMedia')
        ->assertHasErrors('imageUpload');
});
