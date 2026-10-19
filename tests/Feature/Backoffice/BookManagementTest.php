<?php

use App\Enums\Catalog\ProductBadgeEnum;
use App\Enums\Catalog\ProductStatusEnum;
use App\Enums\Catalog\StockTypeEnum;
use App\Livewire\Backoffice\Books\Index;
use App\Models\Book;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Backoffice\Catalog\BookService;
use App\Services\Backoffice\Catalog\CategoryService;
use Livewire\Livewire;

test('guests cannot access book management', function () {
    $this->get(route('manage.books.index'))
        ->assertRedirect(route('login'));
});

test('regular users are redirected from book management', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('manage.books.index'))
        ->assertRedirect(route('user.dashboard'));
});

test('administrators can view book management page', function () {
    $this->actingAs(User::factory()->superAdmin()->create())
        ->get(route('manage.books.index'))
        ->assertOk();
});

test('book service creates a product with book details, then updates and soft deletes it', function () {
    $service = app(BookService::class);

    $book = $service->create([
        'name' => 'Laravel Mastery',
        'author' => 'Taylor Otwell',
        'description' => 'Comprehensive guide to Laravel',
        'price' => 450,
        'pages' => 350,
        'quantity' => 100,
        'status' => ProductStatusEnum::InStock->value,
        'stock_type' => StockTypeEnum::Digital->value,
        'badge' => ProductBadgeEnum::Hot->value,
    ]);

    expect($book->name)->toBe('Laravel Mastery')
        ->and($book->author)->toBe('Taylor Otwell')
        ->and($book->quantity)->toBeNull()
        ->and((float) $book->price)->toBe(450.0);

    $service->update($book->id, [
        'name' => 'Laravel Mastery 2nd Edition',
        'author' => 'Taylor Otwell',
        'price' => 500,
        'pages' => 400,
        'quantity' => 12,
        'status' => ProductStatusEnum::InStock->value,
        'stock_type' => StockTypeEnum::Physical->value,
    ]);

    $book->refresh();

    expect($book->name)->toBe('Laravel Mastery 2nd Edition')
        ->and($book->pages)->toBe(400)
        ->and($book->quantity)->toBe(12);

    $service->delete($book->id);

    expect(Product::find($book->id))->toBeNull()
        ->and(Product::withTrashed()->find($book->id))->not->toBeNull();

    $newBook = $service->create([
        'name' => 'Laravel Mastery 2nd Edition',
        'price' => 300,
        'status' => ProductStatusEnum::InStock->value,
        'stock_type' => StockTypeEnum::Digital->value,
    ]);

    expect($newBook->id)->not->toBe($book->id)
        ->and($newBook->slug)->not->toBe($book->slug);
});

test('book service supports searching, filtering by status and category', function () {
    $service = app(BookService::class);
    $parent = Category::factory()->create();
    $child = Category::factory()->childOf($parent)->create();

    Book::factory()->create(['name' => 'PHP 8.4 Deep Dive', 'category_id' => $child->id]);
    Book::factory()->archived()->create(['name' => 'Advanced Algorithms']);

    expect($service->getBooks(search: 'PHP')->total())->toBe(1)
        ->and($service->getBooks(status: ProductStatusEnum::InStock)->total())->toBe(1)
        ->and($service->getBooks(categoryId: $parent->id)->items()[0]->name)->toBe('PHP 8.4 Deep Dive');
});

test('a book category path runs from the root down to the chosen category', function () {
    $parent = Category::factory()->create(['name' => 'Crops']);
    $child = Category::factory()->childOf($parent)->create(['name' => 'Rice']);

    expect(app(CategoryService::class)->getCategoryLevelIds($child->id))->toBe([$parent->id, $child->id])
        ->and(app(CategoryService::class)->getCategoryLevelIds(null))->toBe([])
        ->and(app(CategoryService::class)->getCategoryLabel($child->id))->toBe('Rice > Crops')
        ->and(app(CategoryService::class)->getCategoryLabel(null))->toBeNull();

    $book = Book::factory()->create(['category_id' => $child->id]);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('viewBook', $book->id)
        ->assertSee('Rice &gt; Crops', false);
});

test('the book category filter loads root categories five at a time and shows sub-categories of the chosen root', function () {
    $roots = collect(range(1, 7))->map(fn (int $i) => Category::factory()->create(['name' => 'Root '.$i]));
    $children = collect(range(1, 6))->map(fn (int $i) => Category::factory()->childOf($roots->first())->create(['name' => 'Sub '.$i]));
    $rootBook = Book::factory()->create(['name' => 'Root Book', 'category_id' => $roots->first()->id]);
    $subBook = Book::factory()->create(['name' => 'Sub Book', 'category_id' => $children->first()->id]);
    Book::factory()->create(['name' => 'Other Book', 'category_id' => $roots->last()->id]);

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

    expect($component->get('books')->pluck('id')->sort()->values()->all())->toBe(collect([$rootBook->id, $subBook->id])->sort()->values()->all());

    $component->set('categoryFilter.1', (string) $children->first()->id);

    expect($component->get('books')->pluck('id')->all())->toBe([$subBook->id]);

    $component->set('categoryFilter.0', (string) $roots->last()->id)->assertSet('categoryFilter', [(string) $roots->last()->id]);
});

test('administrators can create, update, and delete books via livewire', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $category = Category::factory()->create();

    Livewire::test(Index::class)
        ->set('name', 'Refactoring')
        ->set('author', 'Martin Fowler')
        ->set('price', '650')
        ->set('discountPercentage', '10')
        ->set('categoryLevels', [(string) $category->id])
        ->set('bookStatus', ProductStatusEnum::InStock->value)
        ->set('bookStockType', StockTypeEnum::Physical->value)
        ->call('saveBook')
        ->assertHasNoErrors();

    $book = Book::query()->where('name', 'Refactoring')->firstOrFail();

    expect($book->author)->toBe('Martin Fowler')
        ->and($book->category_id)->toBe($category->id)
        ->and($book->final_price)->toBe(585);

    Livewire::test(Index::class)
        ->call('editBook', $book->id)
        ->set('pages', '420')
        ->call('saveBook')
        ->assertHasNoErrors();

    expect($book->refresh()->pages)->toBe(420);

    Livewire::test(Index::class)
        ->call('confirmDeleteBook', $book->id)
        ->call('deleteBook');

    expect(Product::find($book->id))->toBeNull();
});

test('paid books need a price but free books are saved at zero', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->set('name', 'Paid Book')
        ->call('saveBook')
        ->assertHasErrors(['price' => 'required']);

    Livewire::test(Index::class)
        ->set('name', 'Free Book')
        ->set('categoryLevels', [(string) Category::factory()->create()->id])
        ->set('price', '200')
        ->set('isFree', true)
        ->call('saveBook')
        ->assertHasNoErrors();

    expect((float) Product::where('name', 'Free Book')->value('price'))->toBe(0.0);
});

test('a book needs a category', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->set('name', 'Uncategorised Book')
        ->set('price', '100')
        ->call('saveBook')
        ->assertHasErrors(['categoryLevels.0' => 'required'])
        ->assertSee('The category field is required.');

    expect(Product::where('name', 'Uncategorised Book')->exists())->toBeFalse();
});

test('with three levels a book can sit in a sub-sub-category and the root filter finds it', function () {
    config(['app.category_level' => 3]);
    $root = Category::factory()->create();
    $sub = Category::factory()->childOf($root)->create();
    $leaf = Category::factory()->childOf($sub)->create();

    $component = Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->call('createBook')
        ->assertSeeHtml('wire:model.live="categoryLevels.0"')
        ->assertCount('categoryLevelOptions', 3)
        ->assertSet('categoryLevelOptions.1.disabled', true)
        ->assertSet('categoryLevelOptions.2.disabled', true)
        ->set('categoryLevels.0', (string) $root->id)
        ->assertSet('categoryLevelOptions.1.disabled', false)
        ->set('categoryLevels.1', (string) $sub->id)
        ->assertSet('categoryLevelOptions.2.disabled', false)
        ->set('categoryLevels.2', (string) $leaf->id)
        ->set('name', 'Deep Book')
        ->set('price', '100')
        ->call('saveBook')
        ->assertHasNoErrors();

    $book = Book::firstWhere('name', 'Deep Book');
    expect($book->category_id)->toBe($leaf->id);

    $component->call('editBook', $book->id)->assertSet('categoryLevels', [(string) $root->id, (string) $sub->id, (string) $leaf->id]);

    expect(app(BookService::class)->getBooks(categoryId: $root->id)->pluck('id')->all())->toBe([$book->id]);

    config(['app.category_level' => 2]);

    expect(app(BookService::class)->getBooks(categoryId: $root->id)->total())->toBe(0);
});

test('a book category must stay within the configured level', function () {
    $root = Category::factory()->create();
    $sub = Category::factory()->childOf($root)->create();
    $leaf = Category::factory()->childOf($sub)->create();

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->set('name', 'Too Deep Book')
        ->set('price', '100')
        ->set('categoryLevels', [(string) $root->id, (string) $sub->id, (string) $leaf->id])
        ->call('saveBook')
        ->assertHasErrors('categoryLevels.2');
});

test('administrators can feature and unfeature a book', function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $book = Book::factory()->create(['category_id' => Category::factory()]);

    Livewire::test(Index::class)
        ->call('editBook', $book->id)
        ->set('isFeatured', true)
        ->call('saveBook')
        ->assertHasNoErrors();

    expect($book->refresh()->is_featured)->toBeTrue();

    Livewire::test(Index::class)
        ->call('editBook', $book->id)
        ->assertSet('isFeatured', true)
        ->set('isFeatured', false)
        ->call('saveBook')
        ->assertHasNoErrors();

    expect($book->refresh()->is_featured)->toBeFalse();
});

test('book table renders books and manages modal states', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $book = Book::factory()->physical()->create([
        'name' => 'Domain-Driven Design',
        'price' => 55,
        'discount_percentage' => 15,
        'badge' => ProductBadgeEnum::Hot->value,
    ]);
    $book->update(['author' => 'Eric Evans']);

    Livewire::test(Index::class)
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->assertSee('Domain-Driven Design')
        ->assertSee('In Stock')
        ->call('createBook')
        ->assertSet('showForm', true)
        ->assertSet('name', '')
        ->call('editBook', $book->id)
        ->assertSet('editingBookId', $book->id)
        ->assertSet('name', 'Domain-Driven Design')
        ->assertSet('author', 'Eric Evans')
        ->set('showForm', false)
        ->assertSet('editingBookId', null)
        ->call('viewBook', $book->id)
        ->assertSet('showDetails', true)
        ->assertSet('viewingBookId', $book->id)
        ->assertSee('Eric Evans');
});

test('book descriptions are saved as sanitized rich text', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->set('name', 'Rich Text Book')
        ->set('categoryLevels', [(string) Category::factory()->create()->id])
        ->set('price', '100')
        ->set('description', '<div><strong onclick="alert(1)">Bold</strong> <a href="javascript:alert(1)">bad</a> <a href="https://example.com">good</a><script>alert(1)</script><img src="x" onerror="alert(1)"></div>')
        ->call('saveBook')
        ->assertHasNoErrors();

    expect(Product::where('name', 'Rich Text Book')->value('description'))
        ->toBe('<div><strong>Bold</strong> <a>bad</a> <a href="https://example.com">good</a></div>');
});

test('empty rich text descriptions are stored as null', function () {
    expect(sanitizeRichText('<div><br></div>'))->toBeNull()
        ->and(sanitizeRichText(null))->toBeNull();
});

test('a book is one model stored across the products and books tables', function () {
    $book = Book::factory()->create(['name' => 'Soil Health', 'author' => 'Rahim', 'pages' => 120]);

    $this->assertDatabaseHas('products', ['id' => $book->id, 'type' => 'book', 'name' => 'Soil Health']);
    $this->assertDatabaseHas('books', ['product_id' => $book->id, 'author' => 'Rahim', 'pages' => 120]);

    $book->update(['price' => 300]);
    $book->update(['author' => 'Karim']);

    expect($book->refresh()->author)->toBe('Karim')
        ->and($book->pages)->toBe(120)
        ->and($book->price)->toBe(300)
        ->and(Product::query()->find($book->id))->toBeInstanceOf(Book::class)
        ->and(Book::query()->where('author', 'Karim')->count())->toBe(1);
});
