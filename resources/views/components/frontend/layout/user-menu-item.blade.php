@props([
    'icon',
    'href',
    'current' => false,
])

<flux:menu.item
    :icon="$icon"
    :href="$href"
    :aria-current="$current ? 'page' : null"
    {{ $attributes->class([
        'bg-brand-50! font-medium text-brand-700! **:data-flux-menu-item-icon:text-brand-700! dark:bg-brand-900/60! dark:text-brand-200! dark:**:data-flux-menu-item-icon:text-brand-200!' => $current,
    ]) }}
>
    {{ $slot }}
</flux:menu.item>
