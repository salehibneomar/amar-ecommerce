@props([
    'sidebar' => false,
])

@if($sidebar)
    <a {{ $attributes->merge(['class' => 'flex h-[60px] w-full min-w-0 items-center justify-center px-2']) }}>
        <img
            src="{{ $brandUrls['logo'] }}"
            alt="{{ config('app.name', 'AmarEcommerce') }}"
            class="in-data-flux-sidebar-collapsed-desktop:hidden h-12 w-auto object-contain"
        >
        <x-brand.edition class="ms-1.5 in-data-flux-sidebar-collapsed-desktop:hidden" />
        <img
            src="{{ $brandUrls['icon'] }}"
            alt="{{ config('app.name', 'AmarEcommerce') }}"
            class="hidden in-data-flux-sidebar-collapsed-desktop:block size-12 object-contain"
        >
    </a>
@else
    <flux:brand :name="config('app.name', 'Laravel')" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <x-brand.app-logo-icon class="size-5 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif
