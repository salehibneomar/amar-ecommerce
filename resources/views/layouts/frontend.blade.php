@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'seoType' => 'website',
    'landing' => false,
    'fill' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark scroll-smooth bg-canvas">
    <head>
        @include('partials.head', ['seo' => true])
    </head>
    <body data-panel="frontend" @class(['flex flex-col text-zinc-800 antialiased dark:text-zinc-100', 'h-svh' => $fill, 'min-h-svh' => ! $fill])>
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
