<?php

namespace App\Traits\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;
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
            $this->addMediaCollection(mediaCollection($configPath))
                ->useDisk(mediaDisk($configPath))
                ->acceptsMimeTypes(mediaMimeTypes($configPath))
                ->singleFile();
        }
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach ($this->mediaConfigPaths() as $configPath) {
            if (! isMediaImage($configPath)) {
                continue;
            }

            foreach (mediaConversions($configPath) as $name => $size) {
                $this->addMediaConversion($name)
                    ->performOnCollections(mediaCollection($configPath))
                    ->nonQueued()
                    ->fit(Fit::Crop, $size['width'], $size['height'])
                    ->format(mediaConversionFormat($configPath))
                    ->quality(mediaConversionQuality($configPath));
            }
        }
    }

    public function configuredMedia(string $configPath): ?Media
    {
        return $this->getFirstMedia(mediaCollection($configPath));
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

        if (! $media) {
            return null;
        }

        $conversionName = $conversion && $media->hasGeneratedConversion($conversion)
            ? $conversion
            : '';

        return $media->getUrl($conversionName);
    }

    public function attachMedia(string $configPath, UploadedFile $file): Media
    {
        $storedName = generateMediaName(mediaFilePrefix($configPath) ?? ($this->getAttribute('slug') ?: $this->getAttribute('name')));

        return $this->addMedia($file)
            ->usingName($storedName)
            ->usingFileName($storedName.'.'.($file->extension() ?: $file->getClientOriginalExtension()))
            ->toMediaCollection(mediaCollection($configPath), mediaDisk($configPath));
    }

    public function removeMedia(string $configPath): void
    {
        $this->clearMediaCollection(mediaCollection($configPath));
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
