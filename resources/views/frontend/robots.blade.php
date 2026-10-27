User-agent: *
@if ($seoMetaSite['indexing_enabled'])
Disallow: /{{ adminRoutePrefix() }}
Disallow: /checkout

Sitemap: {{ route('sitemap') }}
@else
Disallow: /
@endif
