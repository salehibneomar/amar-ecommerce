<?php

use App\Enums\User\PermissionEnum;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Settings')] class extends Component {
    /**
     * @return list<array{route: string, icon: string, title: string, description: string}>
     */
    #[Computed]
    public function items(): array
    {
        return array_values(array_filter([
            ['route' => 'security.edit', 'icon' => 'key', 'title' => __('Security'), 'description' => __('Change your password and two-factor authentication')],
            ['route' => 'appearance.edit', 'icon' => 'swatch', 'title' => __('Appearance'), 'description' => __('Choose a light, dark or system theme')],
            Gate::allows(PermissionEnum::ManageOffers->value) ? ['route' => 'manage.offers.index', 'icon' => 'receipt-percent', 'title' => __('Offers'), 'description' => __('Manage discount offers')] : null,
            Gate::allows(PermissionEnum::ManageRoles->value) ? ['route' => 'manage.roles.index', 'icon' => 'shield-check', 'title' => __('Roles'), 'description' => __('Manage staff roles and permissions')] : null,
            Gate::allows(PermissionEnum::ManageSystem->value) ? ['route' => 'system.edit', 'icon' => 'server-stack', 'title' => __('System'), 'description' => __('Branding, maintenance mode and application cache')] : null,
        ]));
    }
}; ?>

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
