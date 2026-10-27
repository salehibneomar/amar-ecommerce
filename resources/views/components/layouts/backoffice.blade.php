<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <x-layouts.head :title="$title ?? null" />
    </head>
    <body class="min-h-screen bg-canvas antialiased">
        <x-backoffice.layout.sidebar />
        <flux:header sticky class="z-10 flex items-center justify-between border-b glass-bar px-4">
            <flux:sidebar.toggle icon="bars-3" class="cursor-pointer max-lg:hidden!" data-bar="true" />
            <x-ui.drawer-trigger name="backoffice-menu">
                <x-ui.button variant="ghost" square bar class="shrink-0 cursor-pointer max-sm:size-8! lg:hidden" icon="bars-3" :aria-label="__('Open menu')" />
            </x-ui.drawer-trigger>
            <x-backoffice.layout.navigation-drawer />
            @hasstack('back-url')
                <x-backoffice.layout.back-button :href="trim(view()->yieldPushContent('back-url'))" class="ms-1 max-lg:hidden!" bar />
            @endif
            <a href="{{ route('dashboard') }}" class="ms-2 flex shrink-0 items-center gap-1.5 lg:hidden" wire:navigate>
                <x-brand.app-logo-icon class="h-9 w-auto" />
                <x-brand.edition />
            </a>
            <flux:spacer />
            <div class="flex items-center gap-1.5 sm:gap-2">
                <x-ui.theme-toggle class="max-lg:hidden!" />
                <livewire:backoffice.notifications.dropdown />
                <x-backoffice.layout.user-menu />
            </div>
        </flux:header>
        <flux:main class="flex min-h-[calc(100dvh-4rem)] flex-col">
            <div class="flex-1">
                {{ $slot }}
            </div>
            <x-brand.copyright class="px-0 pb-0 text-[11px]" />
        </flux:main>

        <x-ui.scroll-to-top />
        <x-ui.flash-toast />
        @fluxScripts
    </body>
</html>
