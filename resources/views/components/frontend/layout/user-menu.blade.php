<x-navigation.user-menu bar>
    <x-ui.menu-item icon="squares-2x2" :href="route('user.dashboard')" :current="request()->routeIs('user.dashboard')" wire:navigate>
        {{ __('Dashboard') }}
    </x-ui.menu-item>
    <x-ui.menu-item icon="user" :href="route('user.profile')" :current="request()->routeIs('user.profile')" wire:navigate>
        {{ __('Profile') }}
    </x-ui.menu-item>
    <x-ui.menu-item icon="shopping-bag" :href="route('user.orders')" :current="request()->routeIs('user.orders*')" wire:navigate>
        {{ __('My orders') }}
    </x-ui.menu-item>
    <x-ui.menu-item icon="cog" :href="route('user.settings')" :current="request()->routeIs('user.settings')" wire:navigate>
        {{ __('Settings') }}
    </x-ui.menu-item>
</x-navigation.user-menu>
