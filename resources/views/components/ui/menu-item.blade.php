@props([
    'current' => false,
])

<flux:menu.item
    icon-variant="outline"
    :aria-current="$current ? 'page' : null"
    {{ $attributes->class([
        'border border-bar-active-line bg-bar-active! font-medium text-bar-active-foreground! **:data-flux-menu-item-icon:text-bar-active-foreground!' => $current,
    ]) }}
>{{ $slot }}</flux:menu.item>
