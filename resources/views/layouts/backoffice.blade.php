<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-canvas antialiased">
        <x-backoffice.layout.sidebar :navigation="backofficeNavigation()" />
        <flux:header sticky class="z-10 flex items-center justify-between border-b border-bar-line bg-bar px-4">
            <flux:sidebar.toggle icon="bars-3" class="cursor-pointer max-sm:size-8!" data-bar="true" />
            @hasstack('back-url')
                <x-backoffice.layout.back-button :href="trim(view()->yieldPushContent('back-url'))" class="ms-1 max-lg:hidden!" bar />
            @endif
            <a href="{{ route('dashboard') }}" class="ms-2 flex shrink-0 items-center gap-1.5 lg:hidden" wire:navigate>
                <x-brand.app-logo-icon class="h-9 w-auto" />
                <x-brand.edition />
            </a>
            <flux:spacer />
            <div class="flex items-center gap-1.5 sm:gap-2">
                <x-ui.theme-toggle variant="bar" class="max-lg:hidden!" />
                <livewire:backoffice.notifications.dropdown />
                <x-navigation.user-menu bar>
                    <x-ui.menu-item :href="route('settings.index')" icon="cog" wire:navigate>{{ __('Settings') }}</x-ui.menu-item>
                </x-navigation.user-menu>
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
