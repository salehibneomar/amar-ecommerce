<?php

namespace App\Services\Backoffice\System;

use App\Enums\System\FrontendCacheKeyEnum;
use App\Enums\System\MaintenanceStatusEnum;
use App\Enums\System\ThemeColorEnum;
use App\Models\SystemSetting;
use App\Traits\Cache\FrontendCacheTrait;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SystemSettingService
{
    use FrontendCacheTrait;

    private const BRAND_MEDIA = [
        'logo' => 'system.media.logo',
        'icon' => 'system.media.icon',
        'favicon' => 'system.media.favicon',
    ];

    /**
     * @var array{maintenance_enabled: bool, maintenance_starts_at: int|null, maintenance_ends_at: int|null, maintenance_secret: string|null, theme_color: string, brand: array<string, string|null>}|null
     */
    private ?array $settings = null;

    public function maintenanceStatus(): MaintenanceStatusEnum
    {
        $settings = $this->settings();
        $now = Date::now()->getTimestamp();

        return match (true) {
            ! $settings['maintenance_enabled'] => MaintenanceStatusEnum::Off,
            $settings['maintenance_ends_at'] !== null && $settings['maintenance_ends_at'] <= $now => MaintenanceStatusEnum::Ended,
            $settings['maintenance_starts_at'] !== null && $settings['maintenance_starts_at'] > $now => MaintenanceStatusEnum::Scheduled,
            default => MaintenanceStatusEnum::Active,
        };
    }

    public function isMaintenanceEnabled(): bool
    {
        return $this->settings()['maintenance_enabled'];
    }

    public function isMaintenanceActive(): bool
    {
        return $this->maintenanceStatus() === MaintenanceStatusEnum::Active;
    }

    public function maintenanceSecret(): ?string
    {
        return $this->settings()['maintenance_secret'];
    }

    public function maintenanceStartsAt(): ?CarbonImmutable
    {
        return $this->toDate($this->settings()['maintenance_starts_at']);
    }

    public function maintenanceEndsAt(): ?CarbonImmutable
    {
        return $this->toDate($this->settings()['maintenance_ends_at']);
    }

    public function saveMaintenance(bool $enabled, ?CarbonInterface $startsAt = null, ?CarbonInterface $endsAt = null, ?string $secret = null): void
    {
        $setting = $this->setting();

        $setting->update([
            'maintenance_enabled' => $enabled,
            'maintenance_starts_at' => $enabled ? $startsAt : null,
            'maintenance_ends_at' => $enabled ? $endsAt : null,
            'maintenance_secret' => $enabled ? ($secret ?? $setting->maintenance_secret ?? Str::random(40)) : null,
        ]);

        $this->settings = null;
    }

    /**
     * @return array{logo: string, icon: string, favicon: string}
     */
    public function brandUrls(): array
    {
        $brand = $this->settings()['brand'];

        return [
            'logo' => $brand['logo'] ?? asset((string) mediaDefaultPath(self::BRAND_MEDIA['logo'])),
            'icon' => $brand['icon'] ?? asset((string) mediaDefaultPath(self::BRAND_MEDIA['icon'])),
            'favicon' => $brand['favicon'] ?? asset((string) mediaDefaultPath(self::BRAND_MEDIA['favicon'])),
        ];
    }

    /**
     * @return array<string, array{image: bool, accept: string, hint: string, media: Media|null, previewUrl: string|null}>
     */
    public function brandFields(): array
    {
        $setting = $this->setting()->load('media');

        return array_map(fn (string $configPath): array => [
            ...$setting->mediaField($configPath),
            'image' => true,
            'previewUrl' => $setting->mediaUrl($configPath),
        ], self::BRAND_MEDIA);
    }

    /**
     * @param  array<string, UploadedFile|null>  $files
     * @param  array<string, bool>  $remove
     */
    public function updateBrandMedia(array $files, array $remove): void
    {
        $setting = $this->setting();

        foreach (self::BRAND_MEDIA as $name => $configPath) {
            $setting->syncMedia($configPath, $files[$name] ?? null, $remove[$name] ?? false);
        }

        $this->settings = null;
    }

    public function themeColor(): ThemeColorEnum
    {
        return ThemeColorEnum::tryFrom($this->settings()['theme_color']) ?? ThemeColorEnum::Default;
    }

    /**
     * @return array{palette: array<int, string>, brand: string, brand_hover: string, brand_dark: string, foreground: string}
     */
    public function theme(?ThemeColorEnum $color = null): array
    {
        $presets = themePresets();
        $preset = $presets[($color ?? $this->themeColor())->value] ?? $presets[ThemeColorEnum::Default->value];
        $palette = $preset['palette'];

        return [
            'palette' => $palette,
            'brand' => $palette[$preset['brand']],
            'brand_hover' => $palette[$preset['brand_hover']],
            'brand_dark' => $palette[$preset['brand_dark']],
            'foreground' => readableTextColor($palette[$preset['brand']], themeTextLight(), themeTextDark()),
        ];
    }

    /**
     * @return array<string, array{label: string, theme: array{palette: array<int, string>, brand: string, brand_hover: string, brand_dark: string, foreground: string}}>
     */
    public function themeOptions(): array
    {
        return array_column(array_map(fn (ThemeColorEnum $color): array => [$color->value, ['label' => $color->label(), 'theme' => $this->theme($color)]], ThemeColorEnum::cases()), 1, 0);
    }

    public function saveThemeColor(ThemeColorEnum $color): void
    {
        $this->setting()->update(['theme_color' => $color]);

        $this->settings = null;
    }

    /**
     * @return array{maintenance_enabled: bool, maintenance_starts_at: int|null, maintenance_ends_at: int|null, maintenance_secret: string|null, theme_color: string, brand: array<string, string|null>}
     */
    private function settings(): array
    {
        return $this->settings ??= $this->rememberFrontendCache(FrontendCacheKeyEnum::SystemSettings, function (): array {
            $setting = SystemSetting::query()->with('media')->first();

            return [
                'maintenance_enabled' => (bool) $setting?->maintenance_enabled,
                'maintenance_starts_at' => $setting?->maintenance_starts_at?->getTimestamp(),
                'maintenance_ends_at' => $setting?->maintenance_ends_at?->getTimestamp(),
                'maintenance_secret' => $setting?->maintenance_secret,
                'theme_color' => $setting?->theme_color->value ?? ThemeColorEnum::Default->value,
                'brand' => array_map(fn (string $configPath): ?string => $setting?->mediaUrl($configPath), self::BRAND_MEDIA),
            ];
        });
    }

    private function setting(): SystemSetting
    {
        return SystemSetting::query()->firstOrCreate();
    }

    private function toDate(?int $timestamp): ?CarbonImmutable
    {
        return $timestamp === null ? null : CarbonImmutable::createFromTimestamp($timestamp, config('app.timezone'));
    }
}
