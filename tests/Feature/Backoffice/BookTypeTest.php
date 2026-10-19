<?php

use App\Enums\Catalog\StockTypeEnum;
use App\Livewire\Backoffice\Books\Index;
use App\Livewire\Frontend\Reader\Index as ReaderIndex;
use App\Models\Book;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Frontend\Account\BookReaderService;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

test('both book types keep the stock type choice', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->call('createBook')
        ->assertSet('bookStockType', StockTypeEnum::Digital->value)
        ->assertSeeHtml('wire:model.live="bookStockType"')
        ->set('name', 'Printed Choice')
        ->set('categoryLevels', [(string) Category::factory()->create()->id])
        ->set('price', '100')
        ->set('bookStockType', StockTypeEnum::Physical->value)
        ->call('saveBook')
        ->assertHasNoErrors();

    expect(Product::firstWhere('name', 'Printed Choice')->stock_type)->toBe(StockTypeEnum::Physical);
});

test('digital only books cannot be saved as physical', function () {
    config(['book.type' => 'digital']);
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->call('createBook')
        ->assertDontSeeHtml('wire:model.live="bookStockType"')
        ->set('name', 'Digital Only')
        ->set('price', '100')
        ->set('bookStockType', StockTypeEnum::Physical->value)
        ->call('saveBook')
        ->assertHasErrors(['bookStockType']);
});

test('physical only books are saved as paid physical books with an uploaded preview kept as it is', function () {
    config(['book.type' => 'physical', 'book.preview.pages' => 1]);
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->call('createBook')
        ->assertSet('bookStockType', StockTypeEnum::Physical->value)
        ->assertDontSeeHtml('wire:model.live="bookStockType"')
        ->assertDontSee(__('Free book'))
        ->set('name', 'Printed Only')
        ->set('categoryLevels', [(string) Category::factory()->create()->id])
        ->set('price', '100')
        ->set('quantity', '5')
        ->set('isFree', true)
        ->call('saveBook')
        ->assertHasNoErrors()
        ->assertSee(__('Preview PDF (sample pages)'));

    $book = Product::firstWhere('name', 'Printed Only');
    expect($book->stock_type)->toBe(StockTypeEnum::Physical)
        ->and($book->is_free)->toBeFalse()
        ->and($book->quantity)->toBe(5);

    Livewire::test(Index::class)->call('uploadMedia', $book->id)->set('fileUpload', realPdf(3))->call('saveMedia')->assertHasNoErrors();

    $preview = $book->refresh()->configuredMedia('book.media.preview');
    expect($book->hasConfiguredMedia('book.media.file'))->toBeFalse()
        ->and(pdfPageCount(file_get_contents($preview->getPath())))->toBe(3);

    Livewire::test(Index::class)->call('editBook', $book->id)->set('price', '150')->call('saveBook')->assertHasNoErrors();

    expect($book->refresh()->configuredMedia('book.media.preview')->id)->toBe($preview->id);

    Livewire::test(Index::class)->call('viewPdf', $book->id)->assertHasNoErrors()->assertSet('showPdfViewer', true);
    $this->get(route('manage.books.file', $book))->assertOk();
});

test('physical only books open only their uploaded preview, even for buyers', function () {
    config(['book.type' => 'physical']);
    $customer = User::factory()->create();
    $book = Book::factory()->physical()->create(['name' => 'Printed Preview']);
    $book->attachMedia('book.media.file', realPdf(2));
    $book->attachMedia('book.media.preview', realPdf(1, 'preview.pdf'));
    Order::factory()->for($customer)->paid()->create()->items()->create([
        'product_id' => $book->id, 'product_name_snapshot' => $book->name, 'quantity' => 1, 'unit_price' => 1, 'line_total' => 1,
    ]);

    $this->get(route('shop.product', $book->slug))->assertSee(__('Read preview'));

    Livewire::actingAs($customer)->test(ReaderIndex::class)
        ->call('openBook', $book->id)
        ->assertSet('isPreview', true)
        ->assertSet('documentId', 'preview-'.$book->id)
        ->assertSet('canDownload', false)
        ->assertSee(__('Free preview: sample pages from the printed book'));

    $document = app(BookReaderService::class)->resolveDocument((string) $book->id);
    expect($document->filename)->toBe($book->configuredMedia('book.media.preview')->file_name);

    $this->actingAs($customer)->get(route('user.books.download', $book->id))->assertNotFound();
});

test('single type shops hide the stock type badge in the books list', function () {
    config(['book.type' => 'digital']);
    Book::factory()->create(['name' => 'Badge Check']);

    Livewire::actingAs(User::factory()->superAdmin()->create())->test(Index::class)
        ->assertSee('Badge Check')
        ->assertDontSee(StockTypeEnum::Digital->label());
});
