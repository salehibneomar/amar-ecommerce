@props([
    'icon' => 'lock',
])

<div {{ $attributes->class(['flex flex-wrap items-center justify-between gap-2 rounded-lg bg-gold-50 px-3 py-1.5 text-sm text-gold-900 dark:bg-gold-900/30 dark:text-gold-200']) }}>
    <span class="flex items-center gap-1.5">
        <span class="material-symbols-outlined text-base">{{ $icon }}</span>
        {{ $slot }}
    </span>
    {{ $action ?? '' }}
</div>
