<x-backoffice.layout.nav-heading>{{ __('Overview') }}</x-backoffice.layout.nav-heading>
<x-backoffice.layout.nav-item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
    {{ __('Dashboard') }}
</x-backoffice.layout.nav-item>

<x-backoffice.layout.nav-item icon="building-storefront" :href="route('home')" wire:navigate>
    {{ __('View Store') }}
</x-backoffice.layout.nav-item>

@foreach (backofficeNavigation() as $group)
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
