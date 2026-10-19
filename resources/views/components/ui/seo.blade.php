@props([
    'title',
    'description',
    'image',
    'type' => 'website',
])

<meta name="description" content="{{ $description }}" />
<link rel="canonical" href="{{ url()->current() }}" />
<meta property="og:type" content="{{ $type }}" />
<meta property="og:site_name" content="{{ config('app.name') }}" />
<meta property="og:title" content="{{ $title }}" />
<meta property="og:description" content="{{ $description }}" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:image" content="{{ url($image) }}" />
<meta name="twitter:card" content="summary_large_image" />
