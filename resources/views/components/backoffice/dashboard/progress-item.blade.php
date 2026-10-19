@props([
    'label',
    'value',
    'percent',
])

<div {{ $attributes }}>
    <div class="flex items-center justify-between text-sm">
        <span class="text-zinc-600 dark:text-zinc-300">{{ $label }}</span>
        <span class="font-medium text-zinc-900 dark:text-white">{{ $value }}</span>
    </div>
    <div class="mt-2 h-2 rounded-full bg-zinc-200 dark:bg-zinc-800">
        <div class="h-full rounded-full bg-brand-600" style="width: {{ $percent }}%"></div>
    </div>
</div>
