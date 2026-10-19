@props([
    'icon',
    'href',
    'current' => false,
])

<x-ui.menu-item
    :icon="$icon"
    :href="$href"
    :aria-current="$current ? 'page' : null"
    {{ $attributes->class([
        'bg-bar-active! font-medium text-bar-active-foreground! **:data-flux-menu-item-icon:text-bar-active-foreground!' => $current,
    ]) }}
>
    {{ $slot }}
</x-ui.menu-item>
