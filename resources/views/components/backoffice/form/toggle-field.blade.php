@props([
    'label',
])

<div class="flex items-center gap-3">
    <span class="text-sm leading-tight text-zinc-600 dark:text-zinc-300">{{ $label }}</span>
    <flux:switch {{ $attributes }} :aria-label="$label" />
</div>
