<?php

namespace App\Models;

use App\Enums\Content\FaqStatusEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Traits\Cache\ClearsFrontendCacheTrait;
use App\Traits\Model\CmsUpdateTrackerTrait;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property FaqStatusEnum $status
 */
class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use ClearsFrontendCacheTrait, CmsUpdateTrackerTrait, HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FaqStatusEnum::class,
        ];
    }

    /**
     * @return list<FrontendCacheKeyEnum>
     */
    public function frontendCacheKeys(): array
    {
        return [FrontendCacheKeyEnum::Faqs];
    }
}
