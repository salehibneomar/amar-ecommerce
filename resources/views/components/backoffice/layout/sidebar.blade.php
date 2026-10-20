<flux:sidebar sticky collapsible
    class="data-flux-sidebar-collapsed-desktop:!w-20 data-flux-sidebar-collapsed-desktop:pt-[2px] border-e glass-bar max-lg:hidden! lg:pt-1">
    <flux:sidebar.header>
        <x-brand.app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
    </flux:sidebar.header>

    <flux:sidebar.nav class="mt-4">
        <x-backoffice.layout.navigation />
    </flux:sidebar.nav>

    <flux:spacer />
</flux:sidebar>
