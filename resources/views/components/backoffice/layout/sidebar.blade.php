@props([
    'navigation' => [],
])

<flux:sidebar sticky collapsible
    class="data-flux-sidebar-collapsed-desktop:!w-20 data-flux-sidebar-collapsed-desktop:pt-[2px] border-e border-line bg-surface lg:pt-1">
    <flux:sidebar.header>
        <x-brand.app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
        <x-ui.theme-toggle size="sm" class="ms-auto lg:hidden!" />
    </flux:sidebar.header>

    <flux:sidebar.nav>
        <x-backoffice.layout.nav-item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
            {{ __('Dashboard') }}
        </x-backoffice.layout.nav-item>

        <x-backoffice.layout.nav-item icon="building-storefront" :href="route('home')" wire:navigate>
            {{ __('Storefront') }}
        </x-backoffice.layout.nav-item>

        @foreach ($navigation as $group)
            @canany(array_column($group['items'], 'permission'))
                @if ($group['heading'])
                    <x-backoffice.layout.nav-heading>{{ __($group['heading']) }}</x-backoffice.layout.nav-heading>
                @endif
                @foreach ($group['items'] as $item)
                    @can($item['permission'])
                        <x-backoffice.layout.nav-item :icon="$item['icon']" :href="route($item['route'])" :current="request()->routeIs($item['active'])" wire:navigate>
                            {{ __($item['label']) }}
                        </x-backoffice.layout.nav-item>
                    @endcan
                @endforeach
            @endcanany
        @endforeach
    </flux:sidebar.nav>

    <flux:spacer />
</flux:sidebar>
