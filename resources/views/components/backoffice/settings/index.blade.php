<section class="w-full">
    <x-backoffice.layout.main-content-container :title="__('Settings')" :subtitle="__('Manage your account and application settings')">
        <flux:card class="p-2 sm:p-4">
            <nav aria-label="{{ __('Settings') }}" class="divide-y divide-line">
                @foreach ($this->items as $item)
                    <x-ui.list-link wire:key="settings-{{ $item['route'] }}" :href="route($item['route'])" :icon="$item['icon']" :title="$item['title']" :description="$item['description']" />
                @endforeach
            </nav>
        </flux:card>
    </x-backoffice.layout.main-content-container>
</section>
