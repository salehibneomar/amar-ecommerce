@props([
    'icon',
    'title',
    'value',
    'href',
    'action',
])

<div {{ $attributes->class(['flex flex-col rounded-2xl shadow-card border border-line bg-surface p-6 transition-colors hover:border-brand-300']) }}>
    <div class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300">
        <span class="material-symbols-outlined">{{ $icon }}</span>
    </div>
    <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
    <p class="mt-1 text-sm break-all text-zinc-600 dark:text-zinc-400">{{ $value }}</p>
    <div class="mt-auto pt-5">
        <flux:button size="sm" variant="outline" :href="$href" target="_blank" rel="noopener">{{ $action }}</flux:button>
    </div>
</div>
