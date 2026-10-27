<?php

namespace App\Traits\Seo;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin Model
 *
 * @method static void deleting(\Closure $callback)
 */
trait SeoMetaTrait
{
    protected static function bootSeoMetaTrait(): void
    {
        static::deleting(fn (self $model) => $model->seoMeta()->first()?->delete());
    }

    /**
     * @return MorphOne<SeoMeta, $this>
     */
    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /**
     * @return array{title: string|null, description: string|null, image: string|null}
     */
    public function seoMetaDefaults(): array
    {
        return ['title' => null, 'description' => null, 'image' => null];
    }
}
