@props([
    'variant' => 'flat',
    'size' => 'md',
])

<flux:button
    variant="subtle"
    square
    :size="$size === 'sm' ? 'sm' : 'base'"
    {{ $attributes->class([
        'cursor-pointer',
        'rounded-full border border-zinc-300 bg-white/80 text-zinc-600 backdrop-blur hover:bg-brand-50 hover:text-brand-700 dark:border-zinc-600 dark:bg-zinc-900/80 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-brand-300' => $variant === 'auth',
        'icon-bold text-white! hover:bg-white/15!' => $variant === 'inverse',
        'text-zinc-600 hover:bg-zinc-100 hover:text-brand-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-brand-300' => ! in_array($variant, ['auth', 'inverse'], true),
    ]) }}
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
</flux:button>
