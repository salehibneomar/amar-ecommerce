@props([
    'icon',
    'label',
])

<flux:tooltip :content="$label">
    <x-ui.button size="xs" square :icon="$icon" :aria-label="$label" {{ $attributes->merge(['variant' => 'filled']) }} />
</flux:tooltip>
