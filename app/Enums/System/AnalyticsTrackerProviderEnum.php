<?php

namespace App\Enums\System;

enum AnalyticsTrackerProviderEnum: string
{
    case GoogleAnalytics = 'google_analytics';
    case MetaPixel = 'meta_pixel';
    case GoogleTagManager = 'google_tag_manager';

    public function label(): string
    {
        return match ($this) {
            self::GoogleAnalytics => __('Google Analytics'),
            self::MetaPixel => __('Meta Pixel'),
            self::GoogleTagManager => __('Google Tag Manager'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::GoogleAnalytics => 'amber',
            self::MetaPixel => 'indigo',
            self::GoogleTagManager => 'blue',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::GoogleAnalytics => __('Your measurement ID from Analytics, Admin, Data streams. Leave this off if Analytics already runs inside Tag Manager.'),
            self::MetaPixel => __('Your Pixel ID from Meta Events Manager. Leave this off if the Pixel already runs inside Tag Manager.'),
            self::GoogleTagManager => __('Your container ID from Tag Manager. Every tag added inside the container receives the shop events.'),
        };
    }

    public function placeholder(): string
    {
        return match ($this) {
            self::GoogleAnalytics => 'G-XXXXXXXXXX',
            self::MetaPixel => '123456789012345',
            self::GoogleTagManager => 'GTM-XXXXXXX',
        };
    }

    public function pattern(): string
    {
        return match ($this) {
            self::GoogleAnalytics => '/^G-[A-Z0-9]{4,12}$/',
            self::MetaPixel => '/^\d{10,20}$/',
            self::GoogleTagManager => '/^GTM-[A-Z0-9]{4,12}$/',
        };
    }

    public function component(): string
    {
        return 'analytics.trackers.'.str_replace('_', '-', $this->value);
    }
}
