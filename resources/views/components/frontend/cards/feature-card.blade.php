@props([
    'icon',
    'title',
    'description',
])

<div {{ $attributes->class(['rounded-2xl shadow-card border border-line bg-surface p-6 transition-colors hover:border-brand-300']) }}>
    <div class="flex size-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300">
        <span class="material-symbols-outlined">{{ $icon }}</span>
    </div>
    <h3 class="mt-4 font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $description }}</p>
</div>
