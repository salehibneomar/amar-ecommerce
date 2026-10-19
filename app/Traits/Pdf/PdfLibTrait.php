<?php

namespace App\Traits\Pdf;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;
use RuntimeException;

trait PdfLibTrait
{
    protected function nodeBinary(): string
    {
        return 'node';
    }

    private function pdfPageCount(string $path): int
    {
        $result = $this->runPdfLib(30, 'count', $path);
        $pageCount = (int) trim($result->output());

        if ($result->failed() || $pageCount < 1) {
            throw new RuntimeException($result->errorOutput());
        }

        return $pageCount;
    }

    private function trimPdf(string $source, int $firstPage, int $lastPage): string
    {
        $destination = tempnam(sys_get_temp_dir(), 'pdf-trim-').'.pdf';
        $result = $this->runPdfLib(120, 'trim', $source, $destination, (string) $firstPage, (string) $lastPage);

        if ($result->failed() || ! is_file($destination) || filesize($destination) === 0) {
            @unlink($destination);

            throw new RuntimeException($result->errorOutput());
        }

        return $destination;
    }

    private function runPdfLib(int $timeout, string ...$arguments): ProcessResult
    {
        return Process::timeout($timeout)->run([$this->nodeBinary(), resource_path('scripts/pdf-lib.js'), ...$arguments]);
    }
}
