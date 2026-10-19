@props([
    'icon' => 'lock',
])

<x-ui.banner tone="accent" size="sm" {{ $attributes->class('flex flex-wrap items-center justify-between gap-2 text-zinc-800 dark:text-zinc-100') }}>
    <span class="flex items-center gap-1.5">
        <span class="material-symbols-outlined text-base text-gold-600 dark:text-gold-400">{{ $icon }}</span>
        {{ $slot }}
    </span>
    {{ $action ?? '' }}
</x-ui.banner>
