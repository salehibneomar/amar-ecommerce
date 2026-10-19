<?php

declare(strict_types=1);

namespace App\Resolvers\Pdf;

use App\Services\Frontend\Account\BookReaderService;
use Trianity\LaravelPdfViewer\Contracts\PdfDocumentResolver;
use Trianity\LaravelPdfViewer\Data\PdfDocument;

final class PdfViewerDocumentResolver implements PdfDocumentResolver
{
    public function __construct(private readonly BookReaderService $bookReaderService) {}

    public function resolve(string $documentId): ?PdfDocument
    {
        return $this->bookReaderService->resolveDocument($documentId);
    }
}
