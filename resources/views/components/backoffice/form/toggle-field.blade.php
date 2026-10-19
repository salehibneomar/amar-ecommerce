@props([
    'label',
])

<div class="flex items-center gap-3">
    <span class="text-sm font-medium text-zinc-800 dark:text-white">{{ $label }}</span>
    <flux:switch {{ $attributes }} :aria-label="$label" />
</div>
