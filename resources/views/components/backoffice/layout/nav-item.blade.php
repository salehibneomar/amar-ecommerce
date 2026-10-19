@props([
    'icon',
    'href',
    'current' => false,
])

<flux:sidebar.item
    :icon="$icon"
    :href="$href"
    :current="$current"
    {{ $attributes->merge(['class' => 'in-data-flux-sidebar-collapsed-desktop:!w-full in-data-flux-sidebar-collapsed-desktop:justify-center !h-11 text-lg [&>div>svg]:!size-5']) }}
>
    {{ $slot }}
</flux:sidebar.item>
