<?php

namespace App\Interfaces\Seo;

use App\Enums\System\FrontendCacheKeyEnum;

interface SeoMetaInterface
{
    /**
     * @return array{title: string|null, description: string|null, image: string|null}
     */
    public function seoMetaDefaults(): array;

    /**
     * @return list<FrontendCacheKeyEnum>
     */
    public function frontendCacheKeys(): array;
}
