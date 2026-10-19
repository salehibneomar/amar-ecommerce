<?php

namespace App\Services\Backoffice\Catalog;

use App\Enums\Catalog\BookTypeEnum;
use App\Models\Book;
use App\Traits\Pdf\PdfLibTrait;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BookPreviewService
{
    use PdfLibTrait;

    protected function nodeBinary(): string
    {
        return (string) bookPreviewConfig('node', 'node');
    }

    public function sync(Book $book): ?Media
    {
        if (BookTypeEnum::current()->isPhysicalOnly()) {
            return $book->configuredMedia('book.media.preview');
        }

        $file = $book->configuredMedia('book.media.file');

        if ($file === null || ! $this->isPreviewable($book, $file)) {
            $book->removeMedia('book.media.preview');

            return null;
        }

        return $this->generate($book, $file);
    }

    public function current(Book $book): ?Media
    {
        if (BookTypeEnum::current()->isPhysicalOnly()) {
            return $book->configuredMedia('book.media.preview');
        }

        $file = $book->configuredMedia('book.media.file');

        if ($file === null || ! $this->isPreviewable($book, $file)) {
            return null;
        }

        $preview = $book->configuredMedia('book.media.preview');

        if ($preview && $preview->getCustomProperty('source_id') === $file->id && $preview->getCustomProperty('pages') === bookPreviewPages()) {
            return $preview;
        }

        return $this->generate($book, $file);
    }

    private function isPreviewable(Book $book, Media $file): bool
    {
        return ! $book->is_free && $this->pageCount($file) > bookPreviewPages();
    }

    private function pageCount(Media $file): int
    {
        if ($file->hasCustomProperty('page_count')) {
            return (int) $file->getCustomProperty('page_count');
        }

        $failureKey = "book-preview-failed:{$file->id}";

        if (Cache::has($failureKey)) {
            return 0;
        }

        try {
            $pageCount = $this->pdfPageCount($file->getPath());
        } catch (RuntimeException $exception) {
            report(new RuntimeException("Book page count failed for media {$file->id}: ".$exception->getMessage()));
            Cache::put($failureKey, true, now()->addMinutes(10));

            return 0;
        }

        $file->setCustomProperty('page_count', $pageCount)->save();

        return $pageCount;
    }

    private function generate(Book $book, Media $file): ?Media
    {
        $failureKey = "book-preview-failed:{$file->id}";

        if (Cache::has($failureKey)) {
            return null;
        }

        try {
            $temporary = $this->trimPdf($file->getPath(), 1, bookPreviewPages());
        } catch (RuntimeException $exception) {
            report(new RuntimeException("Book preview generation failed for media {$file->id}: ".$exception->getMessage()));
            Cache::put($failureKey, true, now()->addMinutes(10));

            return null;
        }

        return $book->addMedia($temporary)
            ->usingName($file->name.'-preview')
            ->usingFileName($file->name.'-preview.pdf')
            ->withCustomProperties(['source_id' => $file->id, 'pages' => bookPreviewPages()])
            ->toMediaCollection(mediaCollection('book.media.preview'), mediaDisk('book.media.preview'));
    }
}
