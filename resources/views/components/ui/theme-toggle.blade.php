@props([
    'variant' => 'flat',
    'size' => 'md',
])

<x-ui.button
    :variant="$variant === 'auth' ? 'outline' : 'subtle'"
    :color="$variant === 'auth' ? 'overlay' : null"
    square
    :size="$size === 'sm' ? 'sm' : 'base'"
    :bar="$variant !== 'auth'"
    {{ $attributes->class('cursor-pointer') }}
    x-data
    x-on:click="$flux.dark = ! $flux.dark"
    :aria-label="__('Toggle dark mode')"
>
    <span @class([
        'material-symbols-outlined dark:hidden!',
        'text-base' => $size === 'sm',
        'text-lg' => $size === 'md',
        'text-xl' => $size === 'lg',
    ])>light_mode</span>
    <span @class([
        'material-symbols-outlined hidden! dark:inline-block!',
        'text-base' => $size === 'sm',
        'text-lg' => $size === 'md',
        'text-xl' => $size === 'lg',
    ])>dark_mode</span>
</x-ui.button>
