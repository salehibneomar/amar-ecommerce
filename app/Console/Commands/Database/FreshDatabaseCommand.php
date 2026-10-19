<?php

namespace App\Console\Commands\Database;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FreshDatabaseCommand extends Command
{
    use ConfirmableTrait;

    /**
     * @var string
     */
    protected $signature = 'app:fresh {--seed : Seed the database after migrating} {--force : Run in production}';

    /**
     * @var string
     */
    protected $description = 'Clear media library items, remove stored media files and rebuild the database with migrate:fresh';

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        if (Schema::hasTable('media')) {
            $this->call('media-library:clear', ['--force' => true]);
        }

        $removed = $this->removeMediaDirectories();
        $this->components->info("Removed {$removed} media directories.");

        return $this->call('migrate:fresh', [
            '--seed' => $this->option('seed'),
            '--force' => true,
        ]);
    }

    private function removeMediaDirectories(): int
    {
        $removed = 0;

        foreach (config('filesystems.disks') as $name => $disk) {
            if (($disk['driver'] ?? null) !== 'local') {
                continue;
            }

            foreach (Storage::disk($name)->directories() as $directory) {
                if (ctype_digit($directory) && Storage::disk($name)->deleteDirectory($directory)) {
                    $removed++;
                }
            }
        }

        return $removed;
    }
}
