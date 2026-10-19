<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

@if ($seo ?? false)
    <x-ui.seo :title="filled($title ?? null) ? $title : config('app.name')" :description="Str::limit(filled($description ?? null) ? $description : seoDescription(), 160)" :image="($image ?? null) ?: (seoImage() ?? $brandUrls['logo'])" :type="$seoType ?? 'website'" />
@endif
@if (! ($seo ?? false) || ! isSeoIndexingEnabled())
    <meta name="robots" content="noindex, nofollow" />
@endif

<link rel="icon" href="{{ $brandUrls['favicon'] }}">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400..700,0..1,0" rel="stylesheet">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
