<?php

use App\Livewire\Frontend\Reader\Index;
use App\Livewire\Frontend\Shop\Index as ShopIndex;
use App\Models\Book;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Backoffice\Catalog\BookPreviewService;
use App\Services\Frontend\Account\BookReaderService;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function readableBook(array $attributes = []): Product
{
    Storage::fake('local');
    $book = Book::factory()->create($attributes);
    $book->attachMedia('book.media.file', realPdf(2));

    return $book;
}

function buy(User $user, Product $book): void
{
    Order::factory()->for($user)->paid()->create()->items()->create([
        'product_id' => $book->id,
        'product_name_snapshot' => $book->name,
        'quantity' => 1,
        'unit_price' => $book->price,
        'line_total' => $book->price,
    ]);
}

test('only free books and books bought in a paid order can be read in full', function () {
    $reader = app(BookReaderService::class);
    $customer = User::factory()->create();
    $paid = readableBook();
    $free = readableBook(['is_free' => true]);
    $pending = readableBook();
    Order::factory()->for($customer)->create()->items()->create([
        'product_id' => $pending->id, 'product_name_snapshot' => $pending->name, 'quantity' => 1, 'unit_price' => 1, 'line_total' => 1,
    ]);

    expect($reader->canView($customer, (string) $paid->id))->toBeFalse()
        ->and($reader->canView($customer, 'preview-'.$paid->id))->toBeTrue()
        ->and($reader->canView(null, (string) $free->id))->toBeTrue()
        ->and($reader->canView($customer, (string) $pending->id))->toBeFalse();

    buy($customer, $paid);

    expect($reader->canView($customer, (string) $paid->id))->toBeTrue()
        ->and($reader->canDownload($customer, $paid->id))->toBeTrue()
        ->and($reader->canDownload(User::factory()->create(), $paid->id))->toBeFalse();
});

test('owners open the full book and can download it', function () {
    $customer = User::factory()->create();
    $book = readableBook();
    buy($customer, $book);

    Livewire::actingAs($customer)->test(Index::class)
        ->call('openBook', $book->id)
        ->assertSet('isPreview', false)
        ->assertSet('documentId', (string) $book->id)
        ->assertSet('canDownload', true);

    $this->actingAs($customer)->get(route('user.books.download', $book->id))->assertOk();
});

test('customers who have not bought a book cannot download it', function () {
    $book = readableBook();

    $this->actingAs(User::factory()->create())
        ->get(route('user.books.download', $book->id))
        ->assertNotFound();
});

test('guests open a preview of a paid book from its details page only', function () {
    config(['book.preview.pages' => 1]);
    $book = readableBook(['name' => 'Preview Ready Book']);
    app(BookPreviewService::class)->sync($book);

    Livewire::test(ShopIndex::class)
        ->assertSee('Preview Ready Book')
        ->assertDontSeeHtml("open-book', { bookId: {$book->id} }");

    $this->get(route('shop.product', $book->slug))->assertSee(__('Read preview'));

    Livewire::test(Index::class)
        ->call('openBook', $book->id)
        ->assertSet('isPreview', true)
        ->assertSet('documentId', 'preview-'.$book->id)
        ->assertSet('canDownload', false);
});
