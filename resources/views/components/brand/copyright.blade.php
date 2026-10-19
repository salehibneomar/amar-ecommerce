@props([
    'stacked' => false,
])

<footer {{ $attributes->merge(['class' => 'px-4 py-4 text-center text-xs font-medium text-zinc-700 dark:text-zinc-300']) }}>
    <span>&copy; {{ now()->year }} {{ config('app.name', 'Laravel') }}.</span>
    <span @class(['block' => $stacked])>{{ __('All Rights Reserved') }}</span>
</footer>
