<?php

use App\Livewire\Backoffice\Books\Index;
use App\Models\Book;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Backoffice\Catalog\BookService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Livewire\Livewire;

function fakePdf(string $name = 'book.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n");
}

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

test('administrators can upload and replace book media', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->set('name', 'Media Book')
        ->set('categoryLevels', [(string) Category::factory()->create()->id])
        ->set('price', '100')
        ->call('saveBook')
        ->assertHasNoErrors();

    $book = Product::where('name', 'Media Book')->firstOrFail();
    expect($book->hasConfiguredMedia('book.media.file'))->toBeFalse()
        ->and($book->hasConfiguredMedia('book.media.cover'))->toBeFalse();

    Livewire::test(Index::class)
        ->call('uploadMedia', $book->id)
        ->set('fileUpload', fakePdf('first.pdf'))
        ->set('coverUpload', UploadedFile::fake()->image('first.jpg', 300, 450))
        ->call('saveMedia')
        ->assertHasNoErrors();

    $book->refresh();
    expect($book->hasConfiguredMedia('book.media.file'))->toBeTrue()
        ->and($book->hasConfiguredMedia('book.media.cover'))->toBeTrue()
        ->and($book->configuredMedia('book.media.file')->name)->toStartWith('media-book-')
        ->and($book->configuredMedia('book.media.file')->file_name)
        ->toMatch('/^media-book-\d{5}-\d{20}\.pdf$/');

    foreach (array_keys(mediaConversions('book.media.cover')) as $size) {
        expect($book->configuredMedia('book.media.cover')->hasGeneratedConversion($size))->toBeTrue();
    }

    Livewire::test(Index::class)
        ->call('uploadMedia', $book->id)
        ->set('fileUpload', fakePdf('second.pdf'))
        ->call('saveMedia')
        ->assertHasNoErrors();

    expect($book->refresh()->configuredMedia('book.media.file')->name)->toStartWith('media-book-');
});

test('administrators can access the upload action from the books table', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Book::factory()->create();

    Livewire::test(Index::class)
        ->assertSee('Upload')
        ->assertSeeHtml('wire:click="uploadMedia(')
        ->assertDontSeeHtml('wire:click="viewMedia(');
});

test('the media action opens a preview of the cover and the pdf', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $book = Book::factory()->create();
    $book->attachMedia('book.media.file', fakePdf());
    $book->attachMedia('book.media.cover', UploadedFile::fake()->image('cover.jpg', 600, 900));

    Livewire::test(Index::class)
        ->assertSeeHtml('wire:click="viewMedia('.$book->id.')"')
        ->assertSeeHtml($book->mediaUrl('book.media.cover', mediaSmallestConversion('book.media.cover')))
        ->call('viewMedia', $book->id)
        ->assertSet('showMediaPreview', true)
        ->assertSeeHtml($book->mediaUrl('book.media.cover', 'regular'))
        ->call('viewImage')
        ->assertSet('showImageViewer', true)
        ->assertSeeHtml($book->mediaUrl('book.media.cover', 'large'))
        ->call('viewPdf', $book->id)
        ->assertSet('showPdfViewer', true)
        ->set('showMediaPreview', false)
        ->assertSet('previewBookId', null)
        ->assertSet('showImageViewer', false);
});

test('the image viewer requires a cover image', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $book = Book::factory()->create();
    $book->attachMedia('book.media.file', fakePdf());

    Livewire::test(Index::class)
        ->call('viewMedia', $book->id)
        ->call('viewImage')
        ->assertHasErrors('coverUpload')
        ->assertSet('showImageViewer', false);
});

test('the smallest conversion is resolved from the configured dimensions', function () {
    config(['book.media.cover.conversions' => [
        'large' => ['width' => 800, 'height' => 1200],
        'tiny' => ['width' => 20, 'height' => 30],
        'medium' => ['width' => 300, 'height' => 450],
    ]]);

    expect(mediaSmallestConversion('book.media.cover'))->toBe('tiny')
        ->and(mediaSmallestConversion('book.media.file'))->toBeNull();
});

test('administrators can open the pdf viewer for a book', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $book = Book::factory()->create();
    $book->attachMedia('book.media.file', fakePdf());

    Livewire::test(Index::class)
        ->call('viewPdf', $book->id)
        ->assertSet('showPdfViewer', true)
        ->assertDispatched('pdf-viewer-open');
});

test('the pdf viewer remounts with a fresh key every time it is opened', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $book = Book::factory()->create();
    $book->attachMedia('book.media.file', fakePdf());

    $component = Livewire::test(Index::class)->call('viewPdf', $book->id);
    $firstKey = $component->get('pdfViewerKey');

    $component->set('showPdfViewer', false)
        ->assertSet('pdfBookId', null)
        ->assertSet('pdfViewerKey', null)
        ->call('viewPdf', $book->id)
        ->assertSet('pdfBookId', $book->id);

    expect($component->get('pdfViewerKey'))->not->toBeNull()->not->toBe($firstKey);
});

test('administrators can remove book media', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $book = Book::factory()->create();
    $book->attachMedia('book.media.file',
        fakePdf(),
    );

    Livewire::test(Index::class)
        ->call('uploadMedia', $book->id)
        ->set('removeFile', true)
        ->call('saveMedia')
        ->assertHasNoErrors();

    expect($book->refresh()->hasConfiguredMedia('book.media.file'))->toBeFalse();
});

test('book media validation rejects invalid uploads', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    Livewire::test(Index::class)
        ->set('name', 'Invalid Media Book')
        ->set('categoryLevels', [(string) Category::factory()->create()->id])
        ->set('price', '100')
        ->call('saveBook')
        ->assertHasNoErrors();

    $book = Product::where('name', 'Invalid Media Book')->firstOrFail();

    Livewire::test(Index::class)
        ->call('uploadMedia', $book->id)
        ->set('fileUpload', UploadedFile::fake()->createWithContent('book.txt', 'not a PDF'))
        ->set('coverUpload', fakePdf('cover.pdf'))
        ->call('saveMedia')
        ->assertHasErrors(['fileUpload', 'coverUpload']);

    expect($book->refresh()->hasConfiguredMedia('book.media.cover'))->toBeFalse();
});

test('the upload box shows the configured size limit in megabytes', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $book = Book::factory()->create();

    Livewire::test(Index::class)
        ->call('uploadMedia', $book->id)
        ->assertSee('PDF · '.Number::format(mediaMaxSizeInMb('book.media.file'), maxPrecision: 1).' MB max')
        ->assertSee('JPG, JPEG, PNG, WEBP · '.Number::format(mediaMaxSizeInMb('book.media.cover'), maxPrecision: 1).' MB max');
});

test('deleting a book removes its media while retaining the soft deleted book', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $book = Book::factory()->create(['name' => 'Disposable Book']);
    $book->attachMedia('book.media.file', fakePdf());
    $book->attachMedia('book.media.cover', UploadedFile::fake()->image('cover.jpg'));

    app(BookService::class)->delete($book->id);

    expect(Product::withTrashed()->find($book->id))->not->toBeNull()
        ->and(Product::withTrashed()->find($book->id)->hasMedia('file'))->toBeFalse()
        ->and(Product::withTrashed()->find($book->id)->hasMedia('cover'))->toBeFalse();
});

test('book pdf route requires an administrator and supports inline viewing', function () {
    $book = Book::factory()->create();
    $book->attachMedia('book.media.file',
        fakePdf(),
    );

    $this->actingAs(User::factory()->create())
        ->get(route('manage.books.file', $book))
        ->assertRedirect(route('user.dashboard'));

    $this->actingAs(User::factory()->superAdmin()->create())
        ->withHeaders(['Range' => 'bytes=0-10'])
        ->get(route('manage.books.file', $book))
        ->assertStatus(206)
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Accept-Ranges', 'bytes');
});

test('a book can be deleted right after its media was saved', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $book = Book::factory()->create();

    Livewire::test(Index::class)
        ->call('uploadMedia', $book->id)
        ->set('fileUpload', fakePdf())
        ->call('saveMedia')
        ->assertHasNoErrors()
        ->assertSet('mediaBookId', null)
        ->call('confirmDeleteBook', $book->id)
        ->call('deleteBook')
        ->assertOk();

    expect(Product::find($book->id))->toBeNull();
});

test('book covers are converted to small, regular and large webp images', function () {
    $book = Book::factory()->create();
    $cover = $book->attachMedia('book.media.cover', UploadedFile::fake()->image('cover.jpg', 900, 1350));

    expect(array_keys(mediaConversions('book.media.cover')))->toBe(['small', 'regular', 'large']);

    foreach (['small', 'regular', 'large'] as $size) {
        expect($cover->hasGeneratedConversion($size))->toBeTrue()
            ->and($cover->getPath($size))->toEndWith('.webp');
    }
});

test('uploading a book pdf stores a trimmed preview that follows the pdf', function () {
    skipWithoutPdfLib();
    config(['book.preview.pages' => 2]);
    $this->actingAs(User::factory()->superAdmin()->create());
    $book = Book::factory()->create();

    Livewire::test(Index::class)->call('uploadMedia', $book->id)->set('fileUpload', realPdf(5))->call('saveMedia')->assertHasNoErrors();

    $preview = $book->refresh()->configuredMedia('book.media.preview');
    expect(pdfPageCount(file_get_contents($preview->getPath())))->toBe(2)
        ->and($preview->getCustomProperty('source_id'))->toBe($book->configuredMedia('book.media.file')->id);

    Livewire::test(Index::class)->call('uploadMedia', $book->id)->set('fileUpload', realPdf(3, 'second.pdf'))->call('saveMedia')->assertHasNoErrors();

    $replaced = $book->refresh()->configuredMedia('book.media.preview');
    expect($replaced->id)->not->toBe($preview->id)
        ->and(file_exists($preview->getPath()))->toBeFalse()
        ->and($replaced->getCustomProperty('source_id'))->toBe($book->configuredMedia('book.media.file')->id);

    Livewire::test(Index::class)->call('uploadMedia', $book->id)->set('removeFile', true)->call('saveMedia')->assertHasNoErrors();

    expect($book->refresh()->hasConfiguredMedia('book.media.preview'))->toBeFalse();
});

test('marking a book as free removes its preview and unmarking it creates one again', function () {
    skipWithoutPdfLib();
    config(['book.preview.pages' => 2]);
    $this->actingAs(User::factory()->superAdmin()->create());
    $book = Book::factory()->create(['category_id' => Category::factory()]);

    Livewire::test(Index::class)->call('uploadMedia', $book->id)->set('fileUpload', realPdf(4))->call('saveMedia')->assertHasNoErrors();

    expect($book->refresh()->hasConfiguredMedia('book.media.preview'))->toBeTrue()
        ->and($book->configuredMedia('book.media.file')->getCustomProperty('page_count'))->toBe(4);

    Livewire::test(Index::class)->call('editBook', $book->id)->set('isFree', true)->call('saveBook')->assertHasNoErrors();

    expect($book->refresh()->is_free)->toBeTrue()
        ->and($book->hasConfiguredMedia('book.media.preview'))->toBeFalse();

    Livewire::test(Index::class)->call('editBook', $book->id)->set('isFree', false)->call('saveBook')->assertHasNoErrors();

    expect($book->refresh()->hasConfiguredMedia('book.media.preview'))->toBeTrue();
});

test('book pdfs larger than ten megabytes can be stored up to the upload limit', function () {
    $book = Book::factory()->create();
    $file = UploadedFile::fake()->createWithContent('large.pdf', "%PDF-1.4\n".str_repeat('0', 12 * 1024 * 1024));

    expect(config('media-library.max_file_size'))->toBe(config('book.media.file.max_size') * 1024)
        ->and($book->attachMedia('book.media.file', $file)->size)->toBeGreaterThan(10 * 1024 * 1024);
});
