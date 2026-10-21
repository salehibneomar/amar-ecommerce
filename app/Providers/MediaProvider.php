<?php

namespace App\Providers;

use App\Traits\Cache\ClearsFrontendCacheTrait;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->ensureDiskIsConfigured();
        $this->clearFrontendCacheOnChange();
    }

    private function ensureDiskIsConfigured(): void
    {
        $disk = (string) config('filesystems.media');
        $config = (array) config("filesystems.disks.{$disk}");

        if ($this->app->runningInConsole() || ($config['driver'] ?? null) !== 's3') {
            return;
        }

        $missing = array_keys(array_filter(Arr::only($config, ['key', 'secret', 'bucket', 'endpoint', 'url']), fn (mixed $value): bool => blank($value)));

        if ($missing) {
            throw new RuntimeException("The [{$disk}] media disk is missing: ".implode(', ', $missing).'. Fill them in .env or change MEDIA_DISK.');
        }
    }

    private function clearFrontendCacheOnChange(): void
    {
        $clear = static function (Media $media): void {
            $class = Relation::getMorphedModel($media->model_type) ?? $media->model_type;

            if (in_array(ClearsFrontendCacheTrait::class, class_uses_recursive($class), true) && method_exists($model = new $class, 'clearFrontendCache')) {
                $model->clearFrontendCache();
            }
        };

        Media::saved($clear);
        Media::deleted($clear);
    }
}
