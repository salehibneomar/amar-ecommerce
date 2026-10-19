@props([
    'landing' => false,
])

<header data-popover-anchor class="sticky top-0 z-40 border-b border-brand-700/40 bg-brand-600/85 backdrop-blur dark:border-brand-900/60 dark:bg-brand-950/85">
    <div class="mx-auto flex h-16 max-w-shell items-center gap-2 px-3 sm:gap-4 sm:px-6">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center" wire:navigate>
            <x-brand.app-logo-icon class="h-10 w-auto sm:h-11" />
        </a>

        <nav class="ms-4 hidden items-center gap-1 text-sm font-medium lg:flex">
            <x-frontend.layout.nav-links :landing="$landing" />
        </nav>

        <flux:spacer />

        <div class="flex items-center gap-1.5 sm:gap-2">
            <livewire:frontend.search.index />
            <x-ui.theme-toggle variant="inverse" class="max-lg:hidden!" />
            <livewire:frontend.cart.counter />
            @role('user')
                <livewire:frontend.notifications.dropdown />
            @endrole

            @role('user')
                <x-frontend.layout.user-menu />
            @else
                <div class="hidden items-center gap-2 lg:flex">
                    <flux:button size="sm" variant="ghost" class="text-white! hover:bg-white/15!" :href="route('user.login')" wire:navigate>{{ __('Log in') }}</flux:button>
                    <flux:button size="sm" class="border-0! bg-white! text-brand-700! hover:bg-brand-50!" :href="route('user.register')" wire:navigate>{{ __('Get started') }}</flux:button>
                </div>
            @endrole

            <x-frontend.drawers.trigger name="menu">
                <flux:button variant="ghost" square class="shrink-0 max-sm:size-8! text-white! hover:bg-white/15! lg:hidden" icon="bars-3" :aria-label="__('Open menu')" data-test="mobile-menu-button" />
            </x-frontend.drawers.trigger>
        </div>
    </div>

    <x-frontend.drawers.navigation-drawer :landing="$landing" />
</header>
