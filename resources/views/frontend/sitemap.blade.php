{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ($pages as $page)
        <url><loc>{{ route($page) }}</loc></url>
    @endforeach
    @foreach ($products as $product)
        <url><loc>{{ route('shop.product', $product['slug']) }}</loc>@if ($product['updated_at'])<lastmod>{{ $product['updated_at'] }}</lastmod>@endif</url>
    @endforeach
</urlset>
