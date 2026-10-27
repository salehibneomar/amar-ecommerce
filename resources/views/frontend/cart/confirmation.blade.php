<x-layouts.frontend :title="__('Order placed')">
    <x-frontend.layout.page :title="__('Thank you! Your order is placed.')" :subtitle="__('Order ID: :order', ['order' => $order->public_order_id])">
        <x-frontend.orders.status :order="$order" :contact-links="$contactLinks" :pay-url="$payUrl" />

        <x-frontend.orders.items :order="$order" />

        @auth
            <div class="flex flex-wrap gap-2">
                @role('user')
                    <x-ui.button variant="primary" icon="receipt-percent" :href="route('user.orders.detail', $order->public_order_id)" wire:navigate>{{ __('View Order') }}</x-ui.button>
                @else
                    <x-ui.button variant="primary" icon="squares-2x2" :href="route('dashboard')" wire:navigate>{{ __('Dashboard') }}</x-ui.button>
                @endrole
                <x-ui.button icon="building-storefront" :href="route('shop')" wire:navigate>{{ __('Shop') }}</x-ui.button>
            </div>
        @else
            <x-ui.alert icon="user-plus" color="green" :heading="__('Follow your orders in one place')" :text="__('Create a free account with :email. Your order is added to it as soon as you verify the email.', ['email' => $order->customer_email])">
                <x-slot:actions>
                    <x-ui.button size="sm" :href="route('user.register')" wire:navigate>{{ __('Create Account') }}</x-ui.button>
                </x-slot:actions>
            </x-ui.alert>
        @endauth
    </x-frontend.layout.page>

    <x-analytics.event :event="$analyticsEvent" />
</x-layouts.frontend>
