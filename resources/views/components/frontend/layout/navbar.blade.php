@props([
    'landing' => false,
])

<header data-popover-anchor class="sticky top-0 z-40 border-b glass-bar">
    <div class="mx-auto flex h-16 max-w-shell items-center gap-2 px-3 sm:gap-4 sm:px-6">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-1.5" wire:navigate>
            <x-brand.app-logo-icon class="h-10 w-auto sm:h-11" />
            <x-brand.edition />
        </a>

        <nav class="ms-4 hidden items-center gap-1 text-sm font-medium lg:flex">
            <x-frontend.layout.nav-links :landing="$landing" />
        </nav>

        <flux:spacer />

        <div class="flex items-center gap-1.5 sm:gap-2">
            <livewire:frontend.search.index />
            <x-ui.theme-toggle variant="bar" size="lg" class="max-lg:hidden!" />
            <livewire:frontend.cart.counter />
            @role('user')
                <livewire:frontend.notifications.dropdown />
            @endrole

            @role('user')
                <x-frontend.layout.user-menu />
            @else
                <div class="hidden items-center gap-2 lg:flex">
                    <x-ui.button size="sm" variant="ghost" bar :href="route('user.login')" wire:navigate>{{ __('Log in') }}</x-ui.button>
                    <x-ui.button size="sm" variant="primary" :href="route('user.register')" wire:navigate>{{ __('Get Started') }}</x-ui.button>
                </div>
            @endrole

            <x-frontend.drawers.trigger name="menu">
                <x-ui.button variant="ghost" square bar class="shrink-0 max-sm:size-8! lg:hidden" icon="bars-3" :aria-label="__('Open menu')" data-test="mobile-menu-button" />
            </x-frontend.drawers.trigger>
        </div>
    </div>

    <x-frontend.drawers.navigation-drawer :landing="$landing" />
</header>
