<?php

namespace App\Services\Backoffice\Catalog;

use App\Enums\Catalog\BookTypeEnum;
use App\Enums\Catalog\ProductBadgeEnum;
use App\Enums\Catalog\ProductStatusEnum;
use App\Enums\Catalog\StockTypeEnum;
use App\Models\Book;
use App\Traits\AiAssistant\AiAssistantEmbeddingTrait;
use App\Traits\Query\CategoryTreeTrait;
use App\Traits\Query\TableQueryTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BookService
{
    use AiAssistantEmbeddingTrait, CategoryTreeTrait, TableQueryTrait;

    public function __construct(private readonly BookPreviewService $bookPreviewService) {}

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return [
            'id',
            'name',
            'price',
            'quantity',
            'status',
            'stock_type',
            'badge',
            'created_at',
        ];
    }

    /**
     * @return LengthAwarePaginator<int, Book>
     */
    public function getBooks(
        string $search = '',
        int $perPage = 10,
        ?ProductStatusEnum $status = null,
        ?StockTypeEnum $stockType = null,
        ?int $categoryId = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        return $this->paginateTableQuery(
            query: Book::query()
                ->with(['media', 'category'])
                ->when($categoryId, fn (Builder $query, int $id): Builder => $query->whereIn('category_id', $this->categoriesWithDescendantIds([$id], categoryLevel()))),
            search: $search,
            searchColumns: ['name'],
            filters: [
                'status' => $status,
                'stock_type' => $stockType,
            ],
            perPage: $perPage,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            sortableColumns: $this->sortableColumns(),
        );
    }

    public function getBookById(int|string $id): Book
    {
        return Book::query()->with(['category.parent', 'media'])->findOrFail($id);
    }

    public function findBookById(int|string $id): ?Book
    {
        return Book::query()->with('media')->find($id);
    }

    public function findReadableBook(int|string $id): ?Book
    {
        return Book::query()->with('media')->whereKey($id)->where('status', ProductStatusEnum::InStock)->first();
    }

    public function fileResponse(Book $book, bool $asAttachment = false): Response
    {
        $media = $book->configuredMedia(BookTypeEnum::current()->pdfMedia());
        abort_if($media === null, 404);

        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot();
        abort_unless($disk->exists($path), 404);

        $disposition = $asAttachment ? 'attachment' : 'inline';
        $filename = addcslashes($media->name.'.'.$media->extension, '"\\');

        if ($media->getDiskDriverName() === 'local') {
            return response()->file($disk->path($path), [
                'Content-Type' => $media->mime_type ?: 'application/pdf',
                'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
                'Accept-Ranges' => 'bytes',
            ]);
        }

        return response()->stream(function () use ($disk, $path): void {
            $stream = $disk->readStream($path);

            if (! is_resource($stream)) {
                abort(404);
            }

            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $media->mime_type ?: 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
            'Accept-Ranges' => 'bytes',
            'Content-Length' => (string) $media->size,
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function mediaFields(int|string $id): array
    {
        $book = $this->getBookById($id);

        return [
            'cover' => $book->mediaField('book.media.cover', 'regular'),
            'file' => $book->mediaField(BookTypeEnum::current()->pdfMedia()),
        ];
    }

    public function coverUrl(int|string $id, string $conversion): ?string
    {
        return $this->getBookById($id)->mediaUrl('book.media.cover', $conversion);
    }

    public function hasMedia(int|string $id, string $configPath): bool
    {
        return $this->getBookById($id)->hasConfiguredMedia($configPath);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Book
    {
        $book = DB::transaction(fn (): Book => Book::create($this->bookData($data)));

        $this->syncAiAssistantEmbeddingLater($book);

        return $book;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int|string $id, array $data): bool
    {
        $book = $this->getBookById($id);

        $updated = DB::transaction(fn (): bool => $book->update($this->bookData($data)));

        if ($book->wasChanged('is_free')) {
            $this->bookPreviewService->sync($book);
        }

        $this->syncAiAssistantEmbeddingLater($book);

        return $updated;
    }

    public function delete(int|string $id): bool
    {
        $deleted = (bool) Book::findOrFail($id)->delete();

        return $deleted;
    }

    public function updateMedia(
        int|string $id,
        ?UploadedFile $file = null,
        ?UploadedFile $cover = null,
        bool $removeFile = false,
        bool $removeCover = false,
    ): void {
        $book = Book::findOrFail($id);

        $book->syncMedia(BookTypeEnum::current()->pdfMedia(), $file, $removeFile);
        $book->syncMedia('book.media.cover', $cover, $removeCover);

        if ($file || $removeFile) {
            $this->bookPreviewService->sync($book->refresh());
        }

    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function bookData(array $data): array
    {
        $stockType = BookTypeEnum::current()->stockType() ?? StockTypeEnum::from($data['stock_type']);

        return [
            'category_id' => filled($data['category_id'] ?? null) ? (int) $data['category_id'] : null,
            'name' => $data['name'],
            'description' => sanitizeRichText($data['description'] ?? null),
            'price' => (int) ($data['price'] ?? 0),
            'discount_percentage' => filled($data['discount_percentage'] ?? null) ? (int) $data['discount_percentage'] : null,
            'quantity' => $stockType === StockTypeEnum::Physical && filled($data['quantity'] ?? null) ? (int) $data['quantity'] : null,
            'status' => ProductStatusEnum::from($data['status']),
            'stock_type' => $stockType,
            'badge' => ProductBadgeEnum::tryFrom((string) ($data['badge'] ?? '')),
            'is_free' => ! BookTypeEnum::current()->isPhysicalOnly() && (bool) ($data['is_free'] ?? false),
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'author' => ($data['author'] ?? null) ?: null,
            'pages' => filled($data['pages'] ?? null) ? (int) $data['pages'] : null,
        ];
    }
}
