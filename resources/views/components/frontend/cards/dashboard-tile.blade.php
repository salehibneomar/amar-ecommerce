@props([
    'icon',
    'label',
    'value',
    'hint' => null,
    'tone' => 'brand',
])

<x-ui.card {{ $attributes->class('flex flex-col p-5') }}>
    <div class="flex items-start justify-between gap-3">
        <p class="pt-1 text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
        <x-ui.icon-badge :tone="match ($tone) { 'success' => 'success', 'warning' => 'warning', 'muted' => 'neutral', default => 'brand' }">
            <span class="material-symbols-outlined">{{ $icon }}</span>
        </x-ui.icon-badge>
    </div>

    <p class="mt-2 mb-5 text-4xl font-semibold tracking-tight text-zinc-900 tabular-nums dark:text-white">{{ $value }}</p>

    @if ($hint || $slot->isNotEmpty())
        <div class="mt-auto flex min-h-9 flex-wrap items-center justify-between gap-x-3 gap-y-2 border-t border-line pt-3">
            @if ($hint)
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
            @endif
            {{ $slot }}
        </div>
    @endif
</x-ui.card>
