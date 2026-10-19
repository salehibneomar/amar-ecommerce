<?php

namespace App\Services\Frontend\Account;

use App\Enums\Catalog\BookTypeEnum;
use App\Models\User;
use App\Services\Backoffice\Catalog\BookPreviewService;
use App\Services\Backoffice\Catalog\BookService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Trianity\LaravelPdfViewer\Data\PdfDocument;

class BookReaderService
{
    private const PREVIEW_PREFIX = 'preview-';

    public function __construct(
        private readonly BookService $bookService,
        private readonly BookPreviewService $bookPreviewService,
        private readonly BookLibraryService $bookLibraryService,
    ) {}

    /**
     * @return array{title: string, documentId: string, isPreview: bool, canDownload: bool}
     */
    public function open(?User $user, int $bookId): array
    {
        $book = $this->bookService->findReadableBook($bookId);

        if ($book === null || ! $book->hasConfiguredMedia(BookTypeEnum::current()->pdfMedia())) {
            throw ValidationException::withMessages([
                'book' => __('This book is not available to read yet.'),
            ]);
        }

        $ownsBook = ! BookTypeEnum::current()->isPhysicalOnly() && $this->bookLibraryService->ownsProduct($user, $bookId);
        $isPreview = BookTypeEnum::current()->isPhysicalOnly() || (! $ownsBook && ! $book->is_free);

        if ($isPreview && $this->bookPreviewService->current($book) === null) {
            throw ValidationException::withMessages([
                'book' => __('Buy this book to read it.'),
            ]);
        }

        if ($user !== null) {
            $this->bookLibraryService->recordOpen($user, $bookId);
        }

        return [
            'title' => $book->name,
            'documentId' => $isPreview ? self::PREVIEW_PREFIX.$bookId : (string) $bookId,
            'isPreview' => $isPreview,
            'canDownload' => ! $isPreview && ($ownsBook || ($book->is_free && $user !== null)),
        ];
    }

    public function canView(?User $user, string $documentId): bool
    {
        if ($user?->isBackofficeUser()) {
            return true;
        }

        $bookId = $this->bookId($documentId);
        $book = $this->bookService->findReadableBook($bookId);

        return $book !== null && ($this->isPreview($documentId) || $book->is_free || $this->bookLibraryService->ownsProduct($user, $bookId));
    }

    public function canDownload(?User $user, int|string $bookId): bool
    {
        $book = $this->bookService->findReadableBook($bookId);

        return $user !== null && $book !== null && ! BookTypeEnum::current()->isPhysicalOnly() && ($book->is_free || $this->bookLibraryService->ownsProduct($user, $bookId));
    }

    public function resolveDocument(string $documentId): ?PdfDocument
    {
        $book = $this->bookService->findBookById($this->bookId($documentId));

        if ($book === null) {
            return null;
        }

        $media = $this->isPreview($documentId) || BookTypeEnum::current()->isPhysicalOnly()
            ? $this->bookPreviewService->current($book)
            : $book->configuredMedia('book.media.file');

        return $media === null ? null : new PdfDocument(
            disk: $media->disk,
            path: $media->getPathRelativeToRoot(),
            filename: $media->file_name,
            mime: $media->mime_type ?: 'application/pdf',
        );
    }

    private function isPreview(string $documentId): bool
    {
        return str_starts_with($documentId, self::PREVIEW_PREFIX);
    }

    private function bookId(string $documentId): string
    {
        return Str::after($documentId, self::PREVIEW_PREFIX);
    }
}
