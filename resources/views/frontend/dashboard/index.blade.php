<x-layouts.frontend :title="__('Dashboard')">
    <x-frontend.layout.page :title="__('Welcome, :name', ['name' => $user->name])" :subtitle="__('Your orders and account in one place.')">
        <x-slot:actions>
            <x-ui.button variant="primary" size="sm" icon="building-storefront" :href="route('shop')" wire:navigate>{{ __('Shop') }}</x-ui.button>
        </x-slot:actions>

        <div class="grid gap-5 md:grid-cols-3">
            <x-frontend.cards.dashboard-tile icon="receipt_long" :label="__('Orders')" :value="$orderCounts['total']" :hint="__('Member since :date', ['date' => $user->created_at->format('d M Y')])">
                <x-ui.button size="xs" variant="ghost" icon-trailing="arrow-right" class="-me-2" :href="route('user.orders')" wire:navigate>{{ __('View Orders') }}</x-ui.button>
            </x-frontend.cards.dashboard-tile>
            <x-frontend.cards.dashboard-tile icon="local_shipping" :label="__('In Progress')" :value="$orderCounts['active']"
                :hint="$orderCounts['active'] ? __('We will keep you updated.') : __('No orders on the way.')" />
            <x-frontend.cards.dashboard-tile icon="task_alt" tone="success" :label="__('Completed')" :value="$orderCounts['completed']" :hint="__('Thank you for shopping with us.')" />
        </div>

        @if ($recentOrders->isNotEmpty() || $newProducts->isNotEmpty())
            <x-frontend.cards.card :padded="false" @class(['grid overflow-hidden', 'divide-y divide-line lg:grid-cols-2 lg:divide-x lg:divide-y-0' => $recentOrders->isNotEmpty() && $newProducts->isNotEmpty()])>
                @if ($newProducts->isNotEmpty())
                    <x-frontend.cards.card :framed="false" :title="__('Recently added products')" :description="__('The newest products in the shop.')">
                        <x-slot:actions>
                            <x-ui.button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('Browse All') }}</x-ui.button>
                        </x-slot:actions>
                        <div class="grid gap-3">
                            @foreach ($newProducts as $product)
                                <x-frontend.cards.product-list-item wire:key="new-product-{{ $product->id }}" :product="$product" />
                            @endforeach
                        </div>
                    </x-frontend.cards.card>
                @endif

                @if ($recentOrders->isNotEmpty())
                    <x-frontend.cards.card :framed="false" :title="__('Recent orders')">
                        <x-slot:actions>
                            <x-ui.button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('user.orders')" wire:navigate>{{ __('All Orders') }}</x-ui.button>
                        </x-slot:actions>
                        <div class="-mx-6 -mb-6 divide-y divide-line">
                            @foreach ($recentOrders as $order)
                                <x-frontend.orders.row wire:key="recent-order-{{ $order->id }}"
                                    :href="route('user.orders.detail', $order->public_order_id)"
                                    :number="$order->public_order_id"
                                    :date="$order->created_at->format('d M Y')"
                                    :total="$order->total"
                                    :items-count="$order->items_count"
                                    :status="$order->status->label()" :status-color="$order->status->color()"
                                    :payment="$order->payment_status->label()" :payment-color="$order->payment_status->color()" />
                            @endforeach
                        </div>
                    </x-frontend.cards.card>
                @endif
            </x-frontend.cards.card>
        @endif
    </x-frontend.layout.page>
</x-layouts.frontend>
