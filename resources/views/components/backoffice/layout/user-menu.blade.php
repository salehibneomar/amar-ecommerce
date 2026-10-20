<x-navigation.user-menu bar>
    <x-ui.menu-item icon="squares-2x2" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
        {{ __('Dashboard') }}
    </x-ui.menu-item>
    <x-ui.menu-item icon="user" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
        {{ __('Profile') }}
    </x-ui.menu-item>
    <x-ui.menu-item icon="cog" :href="route('settings.index')" :current="request()->routeIs('settings.index', 'appearance.edit', 'security.edit', 'system.edit')" wire:navigate>
        {{ __('Settings') }}
    </x-ui.menu-item>
</x-navigation.user-menu>
