@props([
    'icon',
    'label',
    'value',
    'hint' => null,
    'tone' => 'brand',
])

<div {{ $attributes->class(['rounded-2xl shadow-card border border-line bg-surface p-5']) }}>
    <div class="flex items-start gap-4">
        <span @class([
            'flex size-11 shrink-0 items-center justify-center rounded-xl',
            'bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300' => $tone === 'brand',
            'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300' => $tone === 'success',
            'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' => $tone === 'warning',
            'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' => $tone === 'muted',
        ])>
            <span class="material-symbols-outlined">{{ $icon }}</span>
        </span>
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
</div>
