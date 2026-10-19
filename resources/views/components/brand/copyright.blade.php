@props([
    'stacked' => false,
])

<footer {{ $attributes->merge(['class' => 'px-4 py-4 text-center text-xs text-zinc-500 dark:text-zinc-400']) }}>
    <span>&copy; {{ now()->year }} {{ config('app.name', 'Laravel') }}.</span>
    <span @class(['block' => $stacked])>{{ __('All Rights Reserved') }}</span>
</footer>
