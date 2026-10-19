@props([
    'icon',
    'label',
    'value',
    'hint' => null,
    'tone' => 'brand',
])

<x-ui.card {{ $attributes->class('p-5') }}>
    <div class="flex items-start gap-4">
        <x-ui.icon-badge size="lg" :tone="match ($tone) { 'success' => 'success', 'warning' => 'warning', 'muted' => 'neutral', default => 'brand' }">
            <span class="material-symbols-outlined">{{ $icon }}</span>
        </x-ui.icon-badge>
        <div class="min-w-0">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
            <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $value }}</p>
            @if ($hint)
                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
            @endif
            @if ($slot->isNotEmpty())
                <div class="mt-3">{{ $slot }}</div>
            @endif
        </div>
    </div>
</x-ui.card>
