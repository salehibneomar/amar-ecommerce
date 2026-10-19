<?php

namespace App\Traits\Pdf;

use Illuminate\Support\Facades\Process;
use RuntimeException;

trait GhostscriptTrait
{
    protected function ghostscriptBinary(): string
    {
        return 'gs';
    }

    private function pdfPageCount(string $path): int
    {
        $result = Process::timeout(30)->run([
            $this->ghostscriptBinary(),
            '-q',
            '-dSAFER',
            '--permit-file-read='.$path,
            '-dNODISPLAY',
            '-dNOPAUSE',
            '-dBATCH',
            '-c',
            '('.addcslashes($path, '\\()').') (r) file runpdfbegin pdfpagecount = quit',
        ]);
        $pageCount = (int) trim($result->output());

        if ($result->failed() || $pageCount < 1) {
            throw new RuntimeException($result->errorOutput());
        }

        return $pageCount;
    }

    private function trimPdf(string $source, int $firstPage, int $lastPage): string
    {
        $destination = tempnam(sys_get_temp_dir(), 'pdf-trim-').'.pdf';

        $result = Process::timeout(120)->run([
            $this->ghostscriptBinary(),
            '-q',
            '-dSAFER',
            '-dBATCH',
            '-dNOPAUSE',
            '-sDEVICE=pdfwrite',
            '-dFirstPage='.$firstPage,
            '-dLastPage='.$lastPage,
            '-sOutputFile='.$destination,
            $source,
        ]);

        if ($result->failed() || ! is_file($destination) || filesize($destination) === 0) {
            @unlink($destination);

            throw new RuntimeException($result->errorOutput());
        }

        return $destination;
    }
}
