<?php

namespace App\Services\Backoffice\Seo;

use App\Enums\System\FrontendCacheKeyEnum;
use App\Interfaces\Seo\SeoMetaInterface;
use App\Models\SeoMeta;
use App\Models\SystemSetting;
use App\Services\Backoffice\System\SystemSettingService;
use App\Traits\Cache\FrontendCacheTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SeoMetaService
{
    use FrontendCacheTrait;

    private const IMAGE = 'seo.media.image';

    /**
     * @var array{indexing_enabled: bool, google_site_verification: string|null, title: string|null, description: string|null, image: string|null}|null
     */
    private ?array $site = null;

    public function __construct(private readonly SystemSettingService $systemSettingService) {}

    /**
     * @param  class-string<Model&SeoMetaInterface>  $ownerType
     * @return array{title: string, description: string, noindex: bool}
     */
    public function formValues(string $ownerType, int $ownerId): array
    {
        $seoMeta = $this->query($this->owner($ownerType, $ownerId))->first();

        return [
            'title' => $seoMeta->title ?? '',
            'description' => $seoMeta->description ?? '',
            'noindex' => (bool) $seoMeta?->noindex,
        ];
    }

    /**
     * @param  class-string<Model&SeoMetaInterface>  $ownerType
     * @return array{image: bool, accept: string, hint: string, media: Media|null, previewUrl: string|null}
     */
    public function imageField(string $ownerType, int $ownerId): array
    {
        $seoMeta = $this->query($this->owner($ownerType, $ownerId))->with('media')->first() ?? new SeoMeta;

        return $seoMeta->mediaField(self::IMAGE, 'small');
    }

    /**
     * @param  class-string<Model&SeoMetaInterface>  $ownerType
     * @param  array{title: string|null, description: string|null, noindex: bool}  $data
     */
    public function save(string $ownerType, int $ownerId, array $data, ?UploadedFile $image = null, bool $removeImage = false): void
    {
        $owner = $this->owner($ownerType, $ownerId);
        $seoMeta = $this->query($owner)->firstOrNew();
        $seoMeta->seoable()->associate($owner);
        $seoMeta->fill([
            'title' => filled($data['title']) ? trim($data['title']) : null,
            'description' => filled($data['description']) ? trim($data['description']) : null,
            'noindex' => $data['noindex'],
        ])->save();

        if ($image || $removeImage) {
            $seoMeta->syncMedia(self::IMAGE, $image, $removeImage);
            $seoMeta->touch();
            $seoMeta->clearFrontendCache();
        }

        $this->site = null;
    }

    /**
     * @return class-string<SystemSetting>
     */
    public function siteOwnerType(): string
    {
        return SystemSetting::class;
    }

    public function siteOwnerId(): int
    {
        return SystemSetting::query()->firstOrCreate()->id;
    }

    /**
     * @return array{indexing_enabled: bool, google_site_verification: string|null}
     */
    public function siteSettings(): array
    {
        $site = $this->site();

        return [
            'indexing_enabled' => $site['indexing_enabled'],
            'google_site_verification' => $site['google_site_verification'],
        ];
    }

    public function saveSiteSettings(bool $indexingEnabled, ?string $googleSiteVerification): void
    {
        SystemSetting::query()->firstOrCreate()->update([
            'seo_indexing_enabled' => $indexingEnabled,
            'google_site_verification' => filled($googleSiteVerification) ? trim($googleSiteVerification) : null,
        ]);

        $this->site = null;
    }

    /**
     * @return array{indexing_enabled: bool, google_site_verification: string|null, title: string, description: string, image: string}
     */
    public function siteDefaults(): array
    {
        $site = $this->site();

        return [
            'indexing_enabled' => isSeoIndexingEnabled() && $site['indexing_enabled'],
            'google_site_verification' => $site['google_site_verification'] ?? googleSiteVerification(),
            'title' => $site['title'] ?? (string) config('app.name'),
            'description' => $site['description'] ?? seoDescription(),
            'image' => $site['image'] ?? seoImage() ?? $this->systemSettingService->brandUrls()['logo'],
        ];
    }

    public function isIndexingEnabled(): bool
    {
        return $this->siteDefaults()['indexing_enabled'];
    }

    /**
     * @return array{title: string|null, description: string|null, image: string|null, noindex: bool}
     */
    public function forPage(Model&SeoMetaInterface $page): array
    {
        /** @var SeoMeta|null $seoMeta */
        $seoMeta = $page->getRelationValue('seoMeta');
        $defaults = $page->seoMetaDefaults();

        return [
            'title' => $seoMeta->title ?? $defaults['title'],
            'description' => $seoMeta->description ?? $defaults['description'],
            'image' => $seoMeta?->mediaUrl(self::IMAGE, 'share') ?? $defaults['image'],
            'noindex' => isSeoPageNoindexEnabled() && (bool) $seoMeta?->noindex,
        ];
    }

    /**
     * @param  class-string  $ownerType
     */
    private function owner(string $ownerType, int $ownerId): Model&SeoMetaInterface
    {
        if (! is_subclass_of($ownerType, Model::class) || ! is_subclass_of($ownerType, SeoMetaInterface::class)) {
            throw new InvalidArgumentException("[{$ownerType}] does not support SEO meta.");
        }

        /** @var Model&SeoMetaInterface */
        return $ownerType::query()->findOrFail($ownerId);
    }

    /**
     * @return Builder<SeoMeta>
     */
    private function query(Model $owner): Builder
    {
        return SeoMeta::query()->whereMorphedTo('seoable', $owner);
    }

    /**
     * @return array{indexing_enabled: bool, google_site_verification: string|null, title: string|null, description: string|null, image: string|null}
     */
    private function site(): array
    {
        return $this->site ??= $this->rememberFrontendCacheVariant(FrontendCacheKeyEnum::SystemSettings, ['seo-meta'], function (): array {
            $setting = SystemSetting::query()->with('seoMeta.media')->first();
            $seoMeta = $setting?->seoMeta;

            return [
                'indexing_enabled' => $setting->seo_indexing_enabled ?? true,
                'google_site_verification' => $setting?->google_site_verification,
                'title' => $seoMeta?->title,
                'description' => $seoMeta?->description,
                'image' => $seoMeta?->mediaUrl(self::IMAGE, 'share'),
            ];
        });
    }
}
