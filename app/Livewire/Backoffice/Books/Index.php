<?php

namespace App\Livewire\Backoffice\Books;

use App\Enums\Catalog\BookTypeEnum;
use App\Enums\Catalog\ProductBadgeEnum;
use App\Enums\Catalog\ProductStatusEnum;
use App\Enums\Catalog\StockTypeEnum;
use App\Livewire\Concerns\InteractsWithCategoryLevels;
use App\Livewire\Concerns\InteractsWithTable;
use App\Models\Book;
use App\Services\Backoffice\Catalog\BookService;
use App\Services\Backoffice\Catalog\CategoryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * @property-read Book|null $viewingBook
 */
class Index extends Component
{
    use InteractsWithCategoryLevels, InteractsWithTable, WithFileUploads, WithPagination;

    public string $status = '';

    public bool $showForm = false;

    public bool $showDeleteConfirmation = false;

    public bool $showDetails = false;

    public ?int $editingBookId = null;

    public ?int $deletingBookId = null;

    public ?int $viewingBookId = null;

    public bool $showMediaPreview = false;

    public ?int $previewBookId = null;

    public bool $showImageViewer = false;

    public bool $showPdfViewer = false;

    public ?int $pdfBookId = null;

    public ?string $pdfViewerKey = null;

    public bool $showMediaUpload = false;

    public ?int $mediaBookId = null;

    public ?TemporaryUploadedFile $fileUpload = null;

    public ?TemporaryUploadedFile $coverUpload = null;

    public bool $removeFile = false;

    public bool $removeCover = false;

    public string $name = '';

    public string $author = '';

    public string $price = '';

    public string $discountPercentage = '';

    /** @var list<string> */
    public array $categoryLevels = [];

    /** @var array<int, int> */
    public array $categoryLevelsLimits = [];

    /** @var list<string> */
    public array $categoryFilter = [];

    /** @var array<int, int> */
    public array $categoryFilterLimits = [];

    public string $description = '';

    public string $pages = '';

    public string $quantity = '';

    public string $bookStatus = 'archived';

    public string $bookStockType = 'digital';

    public string $badge = '';

    public bool $isFree = false;

    public bool $isFeatured = false;

    protected BookService $bookService;

    protected CategoryService $categoryService;

    public function mount(): void
    {
        $this->sortBy = 'name';
        $this->sortDirection = 'asc';
    }

    public function boot(BookService $bookService, CategoryService $categoryService): void
    {
        $this->bookService = $bookService;
        $this->categoryService = $categoryService;
    }

    protected function sortableColumns(): array
    {
        return $this->bookService->sortableColumns();
    }

    public function updatedStatus(string $status): void
    {
        $this->status = ProductStatusEnum::tryFrom($status)->value ?? '';
        $this->resetTableFilters();
    }

    public function updatedShowForm(bool $showForm): void
    {
        if (! $showForm) {
            $this->resetForm();
        }
    }

    public function updatedShowDetails(bool $showDetails): void
    {
        if (! $showDetails) {
            $this->viewingBookId = null;
        }
    }

    public function updatedShowMediaPreview(bool $showMediaPreview): void
    {
        if (! $showMediaPreview) {
            $this->reset(['previewBookId', 'showImageViewer']);
        }
    }

    public function updatedShowPdfViewer(bool $showPdfViewer): void
    {
        if (! $showPdfViewer) {
            $this->reset(['pdfBookId', 'pdfViewerKey']);
            $this->dispatch('pdf-viewer-close');
        }
    }

    public function updatedShowMediaUpload(bool $showMediaUpload): void
    {
        if (! $showMediaUpload) {
            $this->resetMediaUpload();
        }
    }

    public function createBook(): void
    {
        $this->resetForm();
        $this->showDetails = false;
        $this->viewingBookId = null;
        $this->showForm = true;
    }

    public function viewBook(int $bookId): void
    {
        $this->viewingBookId = $bookId;
        $this->showDetails = true;
    }

    public function viewMedia(int $bookId): void
    {
        $this->previewBookId = $this->bookService->getBookById($bookId)->id;
        unset($this->previewBook);
        $this->showMediaPreview = true;
    }

    public function viewImage(): void
    {
        if (! $this->previewBookId || ! $this->bookService->hasMedia($this->previewBookId, 'book.media.cover')) {
            throw ValidationException::withMessages([
                'coverUpload' => __('This book does not have a cover image.'),
            ]);
        }

        $this->showImageViewer = true;
    }

    public function viewPdf(int $bookId): void
    {
        if (! $this->bookService->hasMedia($bookId, BookTypeEnum::current()->pdfMedia())) {
            throw ValidationException::withMessages([
                'fileUpload' => __('This book does not have a PDF file.'),
            ]);
        }

        $this->pdfBookId = $bookId;
        $this->pdfViewerKey = $bookId.'-'.Str::random(12);
        $this->showPdfViewer = true;
        $this->dispatch('pdf-viewer-open');
    }

    public function uploadMedia(int $bookId): void
    {
        $this->resetMediaUpload();
        $this->mediaBookId = $bookId;
        unset($this->mediaBook);
        $this->showMediaUpload = true;
    }

    public function saveMedia(): void
    {
        if (! $this->mediaBookId) {
            throw ValidationException::withMessages([
                'mediaBookId' => __('Select a book before uploading media.'),
            ]);
        }

        $this->validate([
            'fileUpload' => mediaValidationRules(BookTypeEnum::current()->pdfMedia()),
            'coverUpload' => mediaValidationRules('book.media.cover'),
        ]);

        $this->bookService->updateMedia(
            $this->mediaBookId,
            $this->fileUpload,
            $this->coverUpload,
            $this->removeFile,
            $this->removeCover,
        );

        $this->showMediaUpload = false;
        $this->resetMediaUpload();
        unset($this->books, $this->mediaBook);
        $this->dispatch(
            'toast-show',
            slots: ['text' => __('Book media updated successfully.')],
            dataset: ['variant' => 'success'],
        );
    }

    public function editBook(int $bookId): void
    {
        $this->showDetails = false;
        $this->viewingBookId = null;

        $book = $this->bookService->getBookById($bookId);

        $this->editingBookId = $book->id;
        $this->name = $book->name;
        $this->author = $book->author ?? '';
        $this->price = (string) $book->price;
        $this->discountPercentage = $book->discount_percentage !== null ? (string) $book->discount_percentage : '';
        $this->setCategoryLevels('categoryLevels', $this->categoryService->getCategoryLevelIds($book->category_id));
        $this->description = $book->description ?? '';
        $this->pages = $book->pages !== null ? (string) $book->pages : '';
        $this->quantity = $book->quantity !== null ? (string) $book->quantity : '';
        $this->bookStatus = $book->status->value;
        $this->bookStockType = (BookTypeEnum::current()->stockType() ?? $book->stock_type)->value;
        $this->badge = $book->badge->value ?? '';
        $this->isFree = ! BookTypeEnum::current()->isPhysicalOnly() && $book->is_free;
        $this->isFeatured = $book->is_featured;
        $this->showForm = true;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return ['categoryLevels.0' => __('category'), 'categoryLevels.*' => __('category')];
    }

    public function saveBook(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'price' => [Rule::requiredIf(! $this->isFree), 'nullable', 'integer', 'min:0', 'max:99999999'],
            'discountPercentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'categoryLevels' => ['array'],
            'categoryLevels.0' => ['required'],
            'categoryLevels.*' => ['nullable', 'integer', Rule::exists('categories', 'id')->where(fn ($query) => $query->where('depth', '<=', categoryLevel()))->withoutTrashed()],
            'description' => [
                'nullable',
                'string',
            ],
            'pages' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'quantity' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'bookStatus' => [
                'required',
                Rule::enum(ProductStatusEnum::class),
            ],
            'bookStockType' => [
                'required',
                Rule::enum(StockTypeEnum::class)->only(BookTypeEnum::current()->stockTypes()),
            ],
            'badge' => [
                'nullable',
                Rule::enum(ProductBadgeEnum::class),
            ],
            'isFree' => [
                'boolean',
            ],
            'isFeatured' => [
                'boolean',
            ],
        ]);

        $data = [
            'name' => $validated['name'],
            'author' => $validated['author'],
            'price' => $validated['isFree'] ? 0 : $validated['price'],
            'discount_percentage' => $validated['isFree'] ? null : $validated['discountPercentage'],
            'category_id' => $this->selectedCategoryLevelId('categoryLevels'),
            'description' => $validated['description'],
            'pages' => $validated['pages'],
            'quantity' => $validated['quantity'],
            'status' => $validated['bookStatus'],
            'stock_type' => $validated['bookStockType'],
            'badge' => $validated['badge'],
            'is_free' => $validated['isFree'],
            'is_featured' => $validated['isFeatured'],
        ];

        $createdBookId = null;

        if ($this->editingBookId) {
            $this->bookService->update($this->editingBookId, $data);
            $message = __('Book updated successfully.');
        } else {
            $createdBookId = $this->bookService->create($data)->id;
            $message = BookTypeEnum::current()->isPhysicalOnly()
                ? __('Book created successfully. Upload the cover and preview PDF next.')
                : __('Book created successfully. Upload the cover and PDF next.');
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->books);
        $this->dispatch(
            'toast-show',
            slots: ['text' => $message],
            dataset: ['variant' => 'success'],
        );

        if ($createdBookId) {
            $this->uploadMedia($createdBookId);
        }
    }

    public function confirmDeleteBook(int $bookId): void
    {
        $this->deletingBookId = $bookId;
        $this->showDeleteConfirmation = true;
    }

    public function deleteBook(): void
    {
        if (! $this->deletingBookId) {
            throw ValidationException::withMessages([
                'deletingBookId' => __(
                    'Select a book before deleting it.',
                ),
            ]);
        }

        $this->bookService->delete($this->deletingBookId);
        $this->showDeleteConfirmation = false;
        $this->deletingBookId = null;
        unset($this->books);
        $this->dispatch(
            'toast-show',
            slots: ['text' => __('Book deleted successfully.')],
            dataset: ['variant' => 'success'],
        );
    }

    /**
     * @return LengthAwarePaginator<int, Book>
     */
    #[Computed]
    public function books(): LengthAwarePaginator
    {
        return $this->bookService->getBooks(
            search: $this->search,
            perPage: $this->perPage,
            status: ProductStatusEnum::tryFrom($this->status),
            categoryId: $this->selectedCategoryLevelId('categoryFilter'),
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
        );
    }

    #[Computed]
    public function viewingBook(): ?Book
    {
        return $this->viewingBookId
            ? $this->bookService->getBookById($this->viewingBookId)
            : null;
    }

    /**
     * @return list<array{options: array<int, string>, has_more: bool, disabled: bool}>
     */
    #[Computed]
    public function categoryFilterOptions(): array
    {
        return $this->levelOptionsFor('categoryFilter', categoryLevel());
    }

    /**
     * @return list<array{options: array<int, string>, has_more: bool, disabled: bool}>
     */
    #[Computed]
    public function categoryLevelOptions(): array
    {
        return $this->levelOptionsFor('categoryLevels', categoryLevel());
    }

    /**
     * @return array<string, int>
     */
    protected function categoryLevelProperties(): array
    {
        return ['categoryFilter' => 5, 'categoryLevels' => 10];
    }

    protected function categoryLevelsUpdated(string $property): void
    {
        if ($property === 'categoryFilter') {
            $this->resetTableFilters();
        }
    }

    #[Computed]
    public function isPhysicalStock(): bool
    {
        return $this->bookStockType === StockTypeEnum::Physical->value;
    }

    #[Computed]
    public function isPhysicalOnly(): bool
    {
        return BookTypeEnum::current()->isPhysicalOnly();
    }

    #[Computed]
    public function canChooseStockType(): bool
    {
        return count(BookTypeEnum::current()->stockTypes()) > 1;
    }

    #[Computed]
    public function viewingBookCategory(): ?string
    {
        return $this->categoryService->getCategoryLabel($this->viewingBook?->category_id);
    }

    #[Computed]
    public function viewingBookIsPhysical(): bool
    {
        return $this->viewingBook?->stock_type === StockTypeEnum::Physical;
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return collect(ProductStatusEnum::cases())
            ->mapWithKeys(
                fn (ProductStatusEnum $status) => [
                    $status->value => $status->label(),
                ],
            )
            ->all();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function stockTypeOptions(): array
    {
        return collect(BookTypeEnum::current()->stockTypes())
            ->mapWithKeys(
                fn (StockTypeEnum $type) => [
                    $type->value => $type->label(),
                ],
            )
            ->all();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function badgeOptions(): array
    {
        return collect(ProductBadgeEnum::cases())
            ->mapWithKeys(
                fn (ProductBadgeEnum $badge) => [
                    $badge->value => $badge->label(),
                ],
            )
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function uploadFields(): array
    {
        return $this->mediaBookId ? $this->bookService->mediaFields($this->mediaBookId) : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function previewFields(): array
    {
        return $this->previewBookId ? $this->bookService->mediaFields($this->previewBookId) : [];
    }

    #[Computed]
    public function coverImageUrl(): ?string
    {
        return $this->previewBookId ? $this->bookService->coverUrl($this->previewBookId, 'large') : null;
    }

    #[Computed]
    public function pdfDownloadUrl(): ?string
    {
        return $this->pdfBookId ? route('manage.books.file', ['book' => $this->pdfBookId, 'download' => 1]) : null;
    }

    public function render(): View
    {
        return view('components.backoffice.books.index');
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingBookId',
            'name',
            'author',
            'price',
            'discountPercentage',
            'categoryLevels',
            'categoryLevelsLimits',
            'description',
            'pages',
            'quantity',
            'badge',
            'isFree',
            'isFeatured',
        ]);
        $this->bookStatus = ProductStatusEnum::Archived->value;
        $this->bookStockType = BookTypeEnum::current()->stockTypes()[0]->value;
        $this->resetValidation();
    }

    #[Computed]
    public function previewBook(): ?Book
    {
        return $this->previewBookId
            ? $this->bookService->getBookById($this->previewBookId)
            : null;
    }

    /**
     * @return Book|null
     */
    #[Computed]
    public function mediaBook()
    {
        return $this->mediaBookId
            ? $this->bookService->getBookById($this->mediaBookId)
            : null;
    }

    private function resetMediaUpload(): void
    {
        $this->reset([
            'mediaBookId',
            'fileUpload',
            'coverUpload',
            'removeFile',
            'removeCover',
        ]);
        $this->resetValidation();
    }
}
