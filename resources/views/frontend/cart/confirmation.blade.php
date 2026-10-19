<x-layouts::frontend :title="__('Order placed')">
    <x-frontend.layout.page :title="__('Thank you! Your order is placed.')" :subtitle="__('Order ID: :order', ['order' => $order->public_order_id])" width="max-w-3xl">
        <x-frontend.orders.status :order="$order" :contact-links="$contactLinks" />

        <x-frontend.orders.items :order="$order" />

        @auth
            <div class="flex flex-wrap gap-2">
                @role('user')
                    <flux:button variant="primary" icon="receipt-percent" :href="route('user.orders.detail', $order->public_order_id)" wire:navigate>{{ __('View order') }}</flux:button>
                @else
                    <flux:button variant="primary" icon="squares-2x2" :href="route('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:button>
                @endrole
                <flux:button icon="building-storefront" :href="route('shop')" wire:navigate>{{ __('Shop') }}</flux:button>
            </div>
        @else
            <flux:callout icon="user-plus" color="green" :heading="__('Read your books after payment')" :text="__('Create a free account with :email. Your order is added to it as soon as you verify the email.', ['email' => $order->customer_email])">
                <x-slot:actions>
                    <flux:button size="sm" :href="route('user.register')" wire:navigate>{{ __('Create account') }}</flux:button>
                </x-slot:actions>
            </flux:callout>
        @endauth
    </x-frontend.layout.page>
</x-layouts::frontend>
