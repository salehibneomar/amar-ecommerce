<?php

namespace App\Livewire\Backoffice\Settings;

use App\Enums\User\PermissionEnum;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    /**
     * @return list<array{route: string, icon: string, title: string, description: string}>
     */
    #[Computed]
    public function items(): array
    {
        return array_values(array_filter([
            ['route' => 'security.edit', 'icon' => 'key', 'title' => __('Security'), 'description' => __('Change your password')],
            ['route' => 'appearance.edit', 'icon' => 'swatch', 'title' => __('Appearance'), 'description' => __('Choose a light, dark or system mode and the brand color')],
            Gate::allows(PermissionEnum::ManageSystem->value) ? ['route' => 'branding.edit', 'icon' => 'photo', 'title' => __('Branding'), 'description' => __('Replace the logo, icon and favicon')] : null,
            Gate::allows(PermissionEnum::ManagePaymentGateways->value) ? ['route' => 'payments.edit', 'icon' => 'credit-card', 'title' => __('Payment Gateways'), 'description' => __('Turn payment gateways on or off, set their order and the bKash or Nagad numbers')] : null,
            Gate::allows(PermissionEnum::ManageOffers->value) ? ['route' => 'manage.offers.index', 'icon' => 'receipt-percent', 'title' => __('Offers'), 'description' => __('Manage discount offers')] : null,
            Gate::allows(PermissionEnum::ManageSeoMeta->value) ? ['route' => 'manage.seo-metas.index', 'icon' => 'magnifying-glass', 'title' => __('SEO'), 'description' => __('Search engine defaults, link previews and Google verification')] : null,
            Gate::allows(PermissionEnum::ManageAnalytics->value) ? ['route' => 'manage.analytics-trackers.index', 'icon' => 'chart-bar', 'title' => __('Analytics'), 'description' => __('Connect Google Analytics, Meta Pixel or Google Tag Manager')] : null,
            Gate::allows(PermissionEnum::ManageRoles->value) ? ['route' => 'manage.roles.index', 'icon' => 'shield-check', 'title' => __('Roles'), 'description' => __('Manage staff roles and permissions')] : null,
            Gate::allows(PermissionEnum::ManageSystem->value) ? ['route' => 'system.edit', 'icon' => 'server-stack', 'title' => __('System'), 'description' => __('Maintenance mode and application cache')] : null,
        ]));
    }

    public function render(): View
    {
        return view('components.backoffice.settings.index');
    }
}
