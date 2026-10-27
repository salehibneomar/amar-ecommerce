<?php

namespace App\Enums\System;

use Illuminate\Support\Facades\Cache;

enum FrontendCacheKeyEnum: string
{
    case Categories = 'frontend.categories';
    case Products = 'frontend.products';
    case Banners = 'frontend.banners';
    case Offers = 'frontend.offers';
    case Faqs = 'frontend.faqs';
    case AiAssistantVectors = 'frontend.ai-assistant-vectors';
    case SystemSettings = 'frontend.system-settings';
    case AnalyticsTrackers = 'frontend.analytics-trackers';
    case PaymentGateways = 'frontend.payment-gateways';

    public function versionKey(): string
    {
        return $this->value.'.version';
    }

    public function forget(): void
    {
        Cache::store(frontendCacheStore())->deleteMultiple([$this->value, $this->versionKey()]);
    }
}
