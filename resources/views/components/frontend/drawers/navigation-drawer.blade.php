@props([
    'landing' => false,
])

<x-frontend.drawers.drawer name="menu" mobile :label="__('Menu')" x-on:click="$event.target.closest('a') && (open = false)">
    <x-slot:header>
        <a href="{{ route('home') }}" class="flex items-center" wire:navigate>
            <x-brand.app-logo-icon class="h-9 w-auto" />
        </a>
        <div class="flex items-center gap-1">
            <x-ui.theme-toggle size="sm" />
            <flux:button variant="ghost" size="sm" icon="x-mark" x-on:click="open = false" :aria-label="__('Close menu')" />
        </div>
    </x-slot:header>

    <nav class="flex flex-col gap-1 font-medium text-zinc-700 dark:text-zinc-200">
        <x-frontend.layout.nav-links :landing="$landing" variant="stacked" />
    </nav>

    @unlessrole('user')
        <x-slot:footer>
            <div class="grid gap-2">
                <flux:button variant="primary" :href="route('user.register')" wire:navigate>{{ __('Get started') }}</flux:button>
                <flux:button :href="route('user.login')" wire:navigate>{{ __('Log in') }}</flux:button>
            </div>
        </x-slot:footer>
    @endunlessrole
</x-frontend.drawers.drawer>
