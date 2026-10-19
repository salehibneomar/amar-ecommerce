<x-navigation.user-menu inverse>
    <x-frontend.layout.user-menu-item icon="squares-2x2" :href="route('user.dashboard')" :current="request()->routeIs('user.dashboard')" wire:navigate>
        {{ __('Dashboard') }}
    </x-frontend.layout.user-menu-item>
    <x-frontend.layout.user-menu-item icon="user" :href="route('user.profile')" :current="request()->routeIs('user.profile')" wire:navigate>
        {{ __('Profile') }}
    </x-frontend.layout.user-menu-item>
    <x-frontend.layout.user-menu-item icon="shopping-bag" :href="route('user.orders')" :current="request()->routeIs('user.orders*')" wire:navigate>
        {{ __('My orders') }}
    </x-frontend.layout.user-menu-item>
    <x-frontend.layout.user-menu-item icon="book-open" :href="route('user.library')" :current="request()->routeIs('user.library')" wire:navigate>
        {{ __('My library') }}
    </x-frontend.layout.user-menu-item>
    <x-frontend.layout.user-menu-item icon="cog" :href="route('user.settings')" :current="request()->routeIs('user.settings')" wire:navigate>
        {{ __('Settings') }}
    </x-frontend.layout.user-menu-item>
</x-navigation.user-menu>
