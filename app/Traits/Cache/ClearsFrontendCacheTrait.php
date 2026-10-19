<?php

namespace App\Traits\Cache;

use App\Enums\System\FrontendCacheKeyEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @method static void created(callable $callback)
 * @method static void updated(callable $callback)
 * @method static void deleted(callable $callback)
 *
 * @mixin Model
 */
trait ClearsFrontendCacheTrait
{
    public static function bootClearsFrontendCacheTrait(): void
    {
        static::created(static fn (self $model) => $model->clearFrontendCache());

        static::updated(static fn (self $model) => $model->clearFrontendCacheWhenShownDataChanged());

        static::deleted(static fn (self $model) => $model->clearFrontendCache());
    }

    public function clearFrontendCacheWhenShownDataChanged(): void
    {
        if (array_diff(array_keys($this->getChanges()), $this->frontendCacheIgnoredAttributes()) !== []) {
            $this->clearFrontendCache();
        }
    }

    public function clearFrontendCache(): void
    {
        DB::afterCommit(function (): void {
            foreach ($this->frontendCacheKeys() as $key) {
                $key->forget();
            }
        });
    }

    /**
     * @return list<FrontendCacheKeyEnum>
     */
    abstract public function frontendCacheKeys(): array;

    /**
     * @return list<string>
     */
    protected function frontendCacheIgnoredAttributes(): array
    {
        return ['updated_at', 'updated_by'];
    }
}
