<?php

namespace App\Services\Backoffice\System;

use Illuminate\Support\Facades\Process;

use function Illuminate\Support\php_binary;

class SystemService
{
    public function clearCache(): void
    {
        $this->runArtisan('optimize:clear');
    }

    public function optimize(): void
    {
        $this->runArtisan('optimize');
    }

    private function runArtisan(string $command): void
    {
        Process::path(base_path())->run([php_binary(), 'artisan', $command])->throw();
    }
}
