@props([
    'icon',
    'href',
    'current' => false,
])

<flux:sidebar.item
    :icon="$icon"
    :href="$href"
    :current="$current"
    {{ $attributes->merge(['class' => 'in-data-flux-sidebar-collapsed-desktop:!w-full in-data-flux-sidebar-collapsed-desktop:justify-center !h-11 text-lg [&>div>svg]:!size-5 text-bar-foreground! hover:bg-bar-hover! hover:text-bar-active-foreground! data-current:border-bar-active-line! data-current:bg-bar-active! data-current:text-bar-active-foreground! data-current:shadow-none!']) }}
>
    {{ $slot }}
</flux:sidebar.item>
