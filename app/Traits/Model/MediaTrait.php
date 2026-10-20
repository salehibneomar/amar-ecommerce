<?php

namespace App\Traits\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin Model
 * @mixin InteractsWithMedia
 *
 * @method static void deleting(\Closure $callback)
 */
trait MediaTrait
{
    protected static function bootMediaTrait(): void
    {
        static::deleting(function (self $model): void {
            foreach ($model->mediaConfigPaths() as $configPath) {
                $model->removeMedia($configPath);
            }
        });
    }

    /**
     * @return list<string>
     */
    public function mediaConfigPaths(): array
    {
        return [];
    }

    public function registerMediaCollections(): void
    {
        foreach ($this->mediaConfigPaths() as $configPath) {
            $collection = $this->addMediaCollection(mediaCollection($configPath))
                ->useDisk(mediaDisk($configPath))
                ->acceptsMimeTypes(mediaMimeTypes($configPath));

            if (mediaMaxFiles($configPath) === 1) {
                $collection->singleFile();
            }
        }
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $source = $media ? $this->imageDimensions($media) : null;

        foreach ($this->mediaConfigPaths() as $configPath) {
            if (! isMediaImage($configPath)) {
                continue;
            }

            foreach (mediaConversions($configPath) as $name => $size) {
                [$width, $height] = $this->unenlargedSize($size, $source);
                $this->addMediaConversion($name)
                    ->performOnCollections(mediaCollection($configPath))
                    ->nonQueued()
                    ->fit(Fit::Crop, $width, $height)
                    ->format(mediaConversionFormat($configPath))
                    ->quality(mediaConversionQuality($configPath));
            }
        }
    }

    public function configuredMedia(string $configPath): ?Media
    {
        return $this->getFirstMedia(mediaCollection($configPath));
    }

    /**
     * @return MediaCollection<int, Media>
     */
    public function configuredMediaItems(string $configPath): MediaCollection
    {
        return $this->getMedia(mediaCollection($configPath));
    }

    public function hasConfiguredMedia(string $configPath): bool
    {
        return $this->hasMedia(mediaCollection($configPath));
    }

    public function hasAnyConfiguredMedia(): bool
    {
        foreach ($this->mediaConfigPaths() as $configPath) {
            if ($this->hasConfiguredMedia($configPath)) {
                return true;
            }
        }

        return false;
    }

    public function thumbnailUrl(string $configPath): ?string
    {
        return $this->mediaUrl($configPath, mediaSmallestConversion($configPath));
    }

    /**
     * @return array{image: bool, accept: string, hint: string, media: Media|null, previewUrl: string|null}
     */
    public function mediaField(string $configPath, ?string $previewSize = null): array
    {
        return [
            'image' => isMediaImage($configPath),
            'accept' => mediaAccept($configPath),
            'hint' => mediaUploadHint($configPath),
            'media' => $this->configuredMedia($configPath),
            'previewUrl' => $previewSize ? $this->mediaUrl($configPath, $previewSize) : null,
        ];
    }

    public function mediaUrl(string $configPath, ?string $conversion = null): ?string
    {
        $media = $this->configuredMedia($configPath);

        return $media ? $this->mediaItemUrl($media, $conversion) : null;
    }

    public function mediaItemUrl(Media $media, ?string $conversion = null): string
    {
        return $media->getUrl($conversion && $media->hasGeneratedConversion($conversion) ? $conversion : '');
    }

    public function attachMedia(string $configPath, UploadedFile $file): Media
    {
        $storedName = generateMediaName(mediaFilePrefix($configPath) ?? ($this->getAttribute('slug') ?: $this->getAttribute('name')));

        $dimensions = isMediaImage($configPath) ? getimagesize($file->getRealPath()) : false;

        return $this->addMedia($file)
            ->withCustomProperties($dimensions ? ['width' => $dimensions[0], 'height' => $dimensions[1]] : [])
            ->usingName($storedName)
            ->usingFileName($storedName.'.'.($file->extension() ?: $file->getClientOriginalExtension()))
            ->toMediaCollection(mediaCollection($configPath), mediaDisk($configPath));
    }

    /**
     * @param  array{width: int, height: int}  $size
     * @param  array{width: int, height: int}|null  $source
     * @return array{0: int, 1: int}
     */
    private function unenlargedSize(array $size, ?array $source): array
    {
        $scale = $source ? min(1, $source['width'] / $size['width'], $source['height'] / $size['height']) : 1;

        return [max(1, (int) round($size['width'] * $scale)), max(1, (int) round($size['height'] * $scale))];
    }

    /**
     * @return array{width: int, height: int}|null
     */
    private function imageDimensions(Media $media): ?array
    {
        $width = (int) $media->getCustomProperty('width');
        $height = (int) $media->getCustomProperty('height');

        if ($width > 0 && $height > 0) {
            return ['width' => $width, 'height' => $height];
        }

        $path = $media->getPath();
        $dimensions = is_file($path) ? getimagesize($path) : false;

        return $dimensions ? ['width' => $dimensions[0], 'height' => $dimensions[1]] : null;
    }

    public function removeMedia(string $configPath): void
    {
        $this->clearMediaCollection(mediaCollection($configPath));
    }

    public function removeMediaItem(string $configPath, int $mediaId): void
    {
        $this->configuredMediaItems($configPath)->firstWhere('id', $mediaId)?->delete();
    }

    /**
     * @param  list<int>  $mediaIds
     */
    public function setMediaItemsOrder(string $configPath, array $mediaIds): void
    {
        $ids = $this->configuredMediaItems($configPath)->pluck('id')->all();
        $ordered = array_values(array_unique(array_intersect($mediaIds, $ids)));

        if ($ids) {
            Media::setNewOrder([...$ordered, ...array_values(array_diff($ids, $ordered))]);
        }
    }

    public function syncMedia(string $configPath, ?UploadedFile $file = null, bool $remove = false): void
    {
        if ($file) {
            $this->attachMedia($configPath, $file);
        } elseif ($remove) {
            $this->removeMedia($configPath);
        }
    }
}
