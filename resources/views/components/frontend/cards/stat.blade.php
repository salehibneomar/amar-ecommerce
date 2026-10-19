@props([
    'value',
    'label',
])

<div {{ $attributes }}>
    <p class="text-3xl font-semibold text-brand-700 dark:text-brand-400">{{ $value }}</p>
    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
</div>
