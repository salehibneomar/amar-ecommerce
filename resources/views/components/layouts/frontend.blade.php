@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'seoType' => 'website',
    'noindex' => false,
    'landing' => false,
    'fill' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth bg-canvas">
    <head>
        <x-layouts.head seo :title="$title" :description="$description" :image="$image" :seo-type="$seoType" :noindex="$noindex" />
        <x-analytics.scripts />
    </head>
    <body data-panel="frontend" @class(['flex flex-col text-zinc-800 antialiased dark:text-zinc-100', 'h-svh' => $fill, 'min-h-svh' => ! $fill])>
        <x-ui.page-loader />

        <x-frontend.layout.navbar :landing="$landing" />

        <main @class(['flex-1', 'flex min-h-112 flex-col' => $fill])>
            {{ $slot }}
        </main>

        <x-frontend.layout.footer />

        <livewire:frontend.cart.index />

        <x-ui.scroll-to-top />
        <x-ui.flash-toast />
        @fluxScripts
    </body>
</html>
