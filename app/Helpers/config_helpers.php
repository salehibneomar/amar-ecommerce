<?php

function mediaConfig(string $configPath, string $key, mixed $default = null): mixed
{
    return config("{$configPath}.{$key}", $default);
}

function mediaType(string $configPath): string
{
    return (string) mediaConfig($configPath, 'type', 'file');
}

function isMediaImage(string $configPath): bool
{
    return mediaType($configPath) === 'image';
}

function mediaCollection(string $configPath): string
{
    return (string) mediaConfig($configPath, 'collection');
}

function mediaFilePrefix(string $configPath): ?string
{
    return mediaConfig($configPath, 'file_prefix');
}

function mediaDisk(string $configPath): string
{
    return (string) mediaConfig($configPath, 'disk', config('filesystems.media'));
}

/**
 * @return list<string>
 */
function mediaExtensions(string $configPath): array
{
    return array_values((array) mediaConfig($configPath, 'extensions', []));
}

/**
 * @return list<string>
 */
function mediaMimeTypes(string $configPath): array
{
    return array_values((array) mediaConfig($configPath, 'mime_types', []));
}

function mediaMaxFiles(string $configPath): int
{
    return max(1, (int) mediaConfig($configPath, 'max_files', 1));
}

function mediaMaxSize(string $configPath): int
{
    return (int) mediaConfig($configPath, 'max_size', 0);
}

function mediaMaxSizeInMb(string $configPath): float
{
    return mediaMaxSize($configPath) / 1024;
}

/**
 * @return array<string, array{width: int, height: int}>
 */
function mediaConversions(string $configPath): array
{
    return (array) mediaConfig($configPath, 'conversions', []);
}

function mediaSmallestConversion(string $configPath): ?string
{
    $conversions = mediaConversions($configPath);

    uasort($conversions, fn (array $a, array $b): int => $a['width'] * $a['height'] <=> $b['width'] * $b['height']);

    return array_key_first($conversions);
}

function mediaConversionFormat(string $configPath): string
{
    return (string) mediaConfig($configPath, 'conversion_format', 'webp');
}

function mediaConversionQuality(string $configPath): int
{
    return (int) mediaConfig($configPath, 'conversion_quality', 85);
}

function mediaDefaultPath(string $configPath): ?string
{
    return mediaConfig($configPath, 'default');
}

function shopConfig(string $key, mixed $default = null): mixed
{
    return config("shop.{$key}", $default);
}

function shopOrderPrefix(): string
{
    return (string) shopConfig('order_prefix', 'AE');
}

function isGlobalOfferOnly(): bool
{
    return (bool) shopConfig('offer_global_only', false);
}

function shopMaxExtraDiscount(): int
{
    return max(0, min(100, (int) shopConfig('max_extra_discount', 50)));
}

function appServerHost(): string
{
    return (string) config('app.server_host', '127.0.0.1');
}

function appEdition(): string
{
    return (string) config('app.edition', '');
}

function appServerPort(): int
{
    return (int) config('app.server_port', 8000);
}

function appCurrency(): string
{
    return (string) config('app.currency', 'BDT');
}

function appCurrencySymbol(): string
{
    return (string) config('app.currency_symbol', '৳');
}

function appEmptyValue(): string
{
    return (string) config('app.empty_value', 'N/A');
}

function adminRoutePrefix(): string
{
    return (string) config('backoffice.route_prefix', 'backoffice');
}

function isSeoIndexingEnabled(): bool
{
    return (bool) config('seo.indexing_enabled', true);
}

function seoDescription(): string
{
    return (string) config('seo.description', '');
}

function seoImage(): ?string
{
    return config('seo.image') ?: null;
}

function categoryLevel(): int
{
    return (int) config('app.category_level', 2);
}

function searchDebounceMs(): int
{
    return (int) config('app.search_debounce_ms', 300);
}

function validPhoneLength(): int
{
    return (int) config('app.valid_phone_length', 11);
}

function shopOwnerEmail(): ?string
{
    return shopConfig('owner_email') ?: null;
}

function shopSupportEmail(): ?string
{
    return shopConfig('support_email') ?: null;
}

function shopMessengerUrl(): ?string
{
    return shopConfig('messenger_url') ?: null;
}

function shopFacebookPageUrl(): ?string
{
    return shopConfig('facebook_page_url') ?: null;
}

function shopFacebookGroupUrl(): ?string
{
    return shopConfig('facebook_group_url') ?: null;
}

function shopWhatsappNumber(): ?string
{
    $number = preg_replace('/\D/', '', (string) shopConfig('whatsapp_number'));

    if ($number === '') {
        return null;
    }

    return str_starts_with($number, '0') ? shopConfig('whatsapp_country_code', '880').substr($number, 1) : $number;
}

function paymentConfig(string $key, mixed $default = null): mixed
{
    return config("payment.{$key}", $default);
}

/**
 * @return list<string>
 */
function paymentGateways(): array
{
    return array_values((array) paymentConfig('enabled', []));
}

function paymentDefaultMethod(): string
{
    return (string) paymentConfig('manual.default_method', 'bkash');
}

function paymentGatewayClass(string $gateway): ?string
{
    return paymentConfig("gateways.{$gateway}") ?: null;
}

function paymentGatewaySetting(string $gateway, string $key): ?string
{
    return (string) paymentConfig("{$gateway}.{$key}") ?: null;
}

function paymentAccountNumber(string $method): ?string
{
    return paymentConfig("manual.numbers.{$method}") ?: null;
}

function aiAssistantConfig(string $key, mixed $default = null): mixed
{
    return config("ai-assistant.{$key}", $default);
}

function aiAssistantChatConfig(string $key): mixed
{
    return aiAssistantConfig("chat.{$key}");
}

function aiAssistantEmbeddingConfig(string $key): mixed
{
    return aiAssistantConfig("embedding.{$key}");
}

function aiAssistantDriverClass(string $driver): ?string
{
    $class = aiAssistantConfig("drivers.{$driver}");

    return is_string($class) && $class !== '' ? $class : null;
}

function isAiAssistantEnabled(): bool
{
    return (bool) aiAssistantConfig('enabled', false)
        && filled(aiAssistantChatConfig('url'))
        && filled(aiAssistantChatConfig('api_key'))
        && filled(aiAssistantChatConfig('model'));
}

function aiAssistantHistoryMessages(): int
{
    return max(0, (int) aiAssistantConfig('history_messages', 12));
}

function aiAssistantMessageMaxLength(): int
{
    return max(1, (int) aiAssistantConfig('message_max_length', 500));
}

function aiAssistantTopMatches(): int
{
    return max(1, (int) aiAssistantConfig('top_matches', 5));
}

function aiAssistantResults(): int
{
    return max(1, (int) aiAssistantConfig('results', 5));
}

/**
 * @return list<string>
 */
function aiAssistantExamplePrompts(): array
{
    return array_values(array_filter((array) aiAssistantConfig('example_prompts', []), is_string(...)));
}

/**
 * @param  array<string, scalar|null>  $replace
 */
function aiPrompt(string $key, array $replace = []): string
{
    $value = config("ai-prompts.{$key}", '');
    $text = is_array($value) ? implode("\n", array_filter($value, is_string(...))) : (string) $value;

    foreach ($replace as $name => $replacement) {
        $text = str_replace(":{$name}", (string) $replacement, $text);
    }

    return $text;
}

/**
 * @return array<string, string>
 */
function aiPromptMap(string $key): array
{
    return array_filter((array) config("ai-prompts.{$key}", []), fn (mixed $value, mixed $name): bool => is_string($name) && is_string($value), ARRAY_FILTER_USE_BOTH);
}

/**
 * @return list<string>
 */
function aiPromptKeys(string $key): array
{
    return array_values(array_filter(array_keys((array) config("ai-prompts.{$key}", [])), is_string(...)));
}

/**
 * @return array<string, list<string>>
 */
function aiPromptGroups(string $key): array
{
    $groups = [];

    foreach ((array) config("ai-prompts.{$key}", []) as $name => $values) {
        if (is_string($name) && is_array($values)) {
            $groups[$name] = array_values(array_filter($values, is_string(...)));
        }
    }

    return $groups;
}

/**
 * @return list<string>
 */
function aiPromptList(string $key): array
{
    return array_values(array_filter((array) config("ai-prompts.{$key}", []), is_string(...)));
}

function aiAssistantMaxOutputTokens(): int
{
    return max(1, (int) aiAssistantConfig('max_output_tokens', 1024));
}

function aiAssistantCacheMinutes(): int
{
    return max(0, (int) aiAssistantConfig('cache_minutes', 60));
}

/**
 * @return list<string>
 */
function aiAssistantModels(): array
{
    return array_values(array_filter((array) aiAssistantConfig('models', []), is_string(...)));
}

function aiAssistantEmbeddingModel(): ?string
{
    return aiAssistantEmbeddingConfig('model') ?: null;
}

function aiAssistantTimeoutSeconds(): int
{
    return max(1, (int) aiAssistantConfig('timeout_seconds', 20));
}

function passwordMinLength(): int
{
    return max(1, (int) config('auth.password_min_length', 6));
}

function throttleAttempts(string $action): int
{
    return (int) config("throttle.{$action}", 1);
}

function isMailQueueEnabled(): bool
{
    return (bool) config('mail.queue.enabled', false);
}

function mailQueueName(): string
{
    return (string) config('mail.queue.name', 'default');
}

function mailQueueMailer(): string
{
    return (string) config('mail.queue.mailer', 'smtp');
}

function mailQueueBackoffSeconds(): int
{
    return (int) config('mail.queue.backoff_seconds', 300);
}

function mailQueueRetryHours(): int
{
    return (int) config('mail.queue.retry_hours', 24);
}

function emailVerificationExpireMinutes(): int
{
    return max(1, (int) config('auth.verification.expire', 15));
}

function emailVerificationResendSeconds(): int
{
    return (int) config('auth.verification.resend_seconds', 300);
}

function passwordResetResendSeconds(): int
{
    return (int) config('auth.passwords.users.throttle', 300);
}

function isFrontendCacheEnabled(): bool
{
    return (bool) config('cache.frontend.enabled', true);
}

function frontendCacheStore(): ?string
{
    return config('cache.frontend.store') ?: null;
}

function frontendCacheTtl(): int
{
    return max(1, (int) config('cache.frontend.ttl_minutes', 180)) * 60;
}

/**
 * @return list<array{heading: string|null, items: list<array{label: string, icon: string, route: string, active: string, permission: string}>}>
 */
function backofficeNavigation(): array
{
    return config('backoffice.navigation', []);
}

/**
 * @return array<string, array<int, string>>
 */
function themePalettes(): array
{
    return config('theme.palettes', []);
}

function themeBrandColor(): string
{
    return themePalettes()['brand'][(int) config('theme.brand', 600)] ?? '#000000';
}

function themeBrandDarkColor(): string
{
    return themePalettes()['brand'][(int) config('theme.brand_dark', 500)] ?? themeBrandColor();
}

function themeBrandForeground(): string
{
    return (string) config('theme.brand_foreground', '#ffffff');
}
