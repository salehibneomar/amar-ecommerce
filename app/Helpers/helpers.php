<?php

use Carbon\CarbonInterface;
use Dom\HTMLDocument;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

function generateMediaName(
    ?string $baseName = 'file',
    ?string $dimension = null,
): string {
    $baseName = Str::slug($baseName ?: 'file');
    $dimensionSuffix = $dimension ? "-{$dimension}" : '';

    return "{$baseName}-".
        mt_rand(10000, 99999).
        '-'.
        now()->format('YmdHisu').
        $dimensionSuffix;
}

/**
 * @return list<File|string>
 */
function mediaValidationRules(string $configPath, bool $required = false): array
{
    $rules = [
        $required ? 'required' : 'nullable',
        File::types([...mediaExtensions($configPath), ...mediaMimeTypes($configPath)])
            ->max(mediaMaxSize($configPath)),
    ];

    if (isMediaImage($configPath)) {
        $rules[] = 'image';
    }

    return $rules;
}

function mediaAccept(string $configPath): string
{
    return implode(',', array_map(fn (string $extension): string => '.'.$extension, mediaExtensions($configPath)));
}

function mediaUploadHint(string $configPath): string
{
    return __(':types · :size MB max', [
        'types' => strtoupper(implode(', ', mediaExtensions($configPath))),
        'size' => Number::format(mediaMaxSizeInMb($configPath), maxPrecision: 1),
    ]);
}

function sanitizeRichText(?string $html): ?string
{
    if (blank(strip_tags((string) $html))) {
        return null;
    }

    $allowedTags = ['DIV', 'P', 'BR', 'STRONG', 'B', 'EM', 'I', 'DEL', 'S', 'A', 'H1', 'BLOCKQUOTE'];
    $body = HTMLDocument::createFromString("<!DOCTYPE html><body>{$html}</body>", LIBXML_NOERROR)->body;

    foreach (array_reverse(iterator_to_array($body->querySelectorAll('*'))) as $element) {
        if (in_array($element->tagName, ['SCRIPT', 'STYLE', 'IFRAME', 'OBJECT', 'EMBED'], true)) {
            $element->remove();

            continue;
        }

        if (! in_array($element->tagName, $allowedTags, true)) {
            $element->replaceWith(...iterator_to_array($element->childNodes));

            continue;
        }

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $isSafeLink = $element->tagName === 'A'
                && $attribute->name === 'href'
                && preg_match('/^(https?:|mailto:)/i', $attribute->value);

            if (! $isSafeLink) {
                $element->removeAttribute($attribute->name);
            }
        }
    }

    return $body->innerHTML;
}

function plainText(?string $html): string
{
    return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html))));
}

/**
 * @return list<int|null>
 */
function paginationPages(int $current, int $last, int $around = 1): array
{
    $pages = array_unique([1, ...range(max(1, $current - $around), min($last, $current + $around)), $last]);
    sort($pages);

    $result = [];

    foreach ($pages as $index => $page) {
        if ($index > 0 && $page > $pages[$index - 1] + 1) {
            $result[] = null;
        }

        $result[] = $page;
    }

    return $result;
}

function formatMoney(int|float|string|null $amount, ?string $symbol = null): string
{
    return ($symbol ?? appCurrencySymbol()).number_format((int) round((float) $amount));
}

function formatDateTime(?CarbonInterface $date): string
{
    return $date?->format(config('app.datetime_format')) ?? '';
}

/**
 * @return array{d: int, h: int, m: int, s: int}
 */
function timeLeftParts(CarbonInterface $date): array
{
    $seconds = max(0, (int) now()->diffInSeconds($date));

    return ['d' => intdiv($seconds, 86400), 'h' => intdiv($seconds % 86400, 3600), 'm' => intdiv($seconds % 3600, 60), 's' => $seconds % 60];
}

function formatTimeLeft(CarbonInterface $date, string $separator = ':'): string
{
    return collect(timeLeftParts($date))
        ->reject(fn (int $count, string $unit): bool => $unit === 'd' && $count === 0)
        ->map(fn (int $count, string $unit): string => sprintf('%02d%s', $count, __($unit)))
        ->join(" {$separator} ");
}

function colorLuminance(string $hex): float
{
    $channels = array_map(fn (string $pair): float => hexdec($pair) / 255, str_split(ltrim($hex, '#'), 2));
    [$red, $green, $blue] = array_map(fn (float $channel): float => $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4, $channels);

    return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
}

function colorContrastRatio(string $first, string $second): float
{
    $luminances = [colorLuminance($first), colorLuminance($second)];

    return (max($luminances) + 0.05) / (min($luminances) + 0.05);
}

function readableTextColor(string $background, string $light = '#ffffff', string $dark = '#000000'): string
{
    return colorContrastRatio($background, $light) >= colorContrastRatio($background, $dark) ? $light : $dark;
}
