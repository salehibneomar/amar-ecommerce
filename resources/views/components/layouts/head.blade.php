@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'seoType' => 'website',
    'seo' => false,
    'noindex' => false,
])

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="theme-color" content="{{ $theme['brand'] }}" />

<title>
    {{ filled($title) ? $title.' - '.config('app.name', 'Laravel') : ($seo ? $seoMetaSite['title'] : config('app.name', 'Laravel')) }}
</title>

@if ($seo)
    <x-ui.seo :title="filled($title) ? $title : $seoMetaSite['title']" :description="Str::limit(filled($description) ? $description : $seoMetaSite['description'], 160)" :image="$image ?: $seoMetaSite['image']" :type="$seoType" />
    @if ($seoMetaSite['google_site_verification'])
        <meta name="google-site-verification" content="{{ $seoMetaSite['google_site_verification'] }}" />
    @endif
@endif
@if (! $seo || ! $seoMetaSite['indexing_enabled'] || $noindex)
    <meta name="robots" content="noindex, nofollow" />
@endif

<link rel="icon" href="{{ $brandUrls['favicon'] }}">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,0" rel="stylesheet">

@fonts

<x-brand.theme-colors />
@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
