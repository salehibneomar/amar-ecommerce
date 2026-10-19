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
            ['route' => 'profile.edit', 'icon' => 'user-circle', 'title' => __('Profile'), 'description' => __('Update your name, email address and photo')],
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
        <flux:card class="border-line bg-surface p-2 shadow-card sm:p-4">
            <nav aria-label="{{ __('Settings') }}" class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($this->items as $item)
                    <a href="{{ route($item['route']) }}" wire:navigate wire:key="settings-{{ $item['route'] }}"
                        class="group flex items-center gap-3 px-3 py-3 transition hover:bg-zinc-100 dark:hover:bg-zinc-800/60">
                        <flux:icon :icon="$item['icon']" variant="outline" class="size-5 shrink-0 text-zinc-600 dark:text-zinc-300" />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $item['title'] }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item['description'] }}</p>
                        </div>
                        <flux:icon.chevron-right variant="micro" class="shrink-0 text-zinc-400 transition group-hover:translate-x-0.5" />
                    </a>
                @endforeach
            </nav>
        </flux:card>
    </x-backoffice.layout.main-content-container>
</section>
