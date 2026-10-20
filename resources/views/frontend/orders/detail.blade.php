<x-layouts::frontend :title="__('Order Details')">
    <x-frontend.layout.page :title="__('Order :order', ['order' => $order->public_order_id])" :subtitle="__('Placed on :date', ['date' => formatDateTime($order->created_at)])">
        <x-slot:actions>
            <div class="flex gap-2">
                <x-ui.button variant="ghost" icon="arrow-left" :href="route('user.orders')" wire:navigate>{{ __('All Orders') }}</x-ui.button>
                @if ($order->has_invoice)
                    <x-ui.button icon="arrow-down-tray" :href="route('user.orders.invoice', $order->public_order_id)">{{ __('Invoice') }}</x-ui.button>
                @endif
                @if ($order->isCancellableByCustomer())
                    <flux:modal.trigger name="cancel-order">
                        <x-ui.button variant="danger" icon="x-circle">{{ __('Cancel Order') }}</x-ui.button>
                    </flux:modal.trigger>
                @endif
            </div>
        </x-slot:actions>

        @if ($order->isCancellableByCustomer())
            <x-ui.modal name="cancel-order" class="w-full max-w-md">
                <form method="POST" action="{{ route('user.orders.cancel', $order->public_order_id) }}" class="space-y-5">
                    @csrf
                    <div class="space-y-2">
                        <flux:heading size="lg">{{ __('Cancel this order?') }}</flux:heading>
                        <flux:text>{{ __('Order :order will be cancelled. This cannot be undone.', ['order' => $order->public_order_id]) }}</flux:text>
                    </div>
                    <x-form.actions>
                        <flux:modal.close>
                            <x-ui.button>{{ __('Keep Order') }}</x-ui.button>
                        </flux:modal.close>
                        <x-ui.button type="submit" variant="danger">{{ __('Cancel Order') }}</x-ui.button>
                    </x-form.actions>
                </form>
            </x-ui.modal>
        @endif

        <x-frontend.orders.status :order="$order" :contact-links="$contactLinks" />

        <div class="grid gap-6 md:grid-cols-[1fr_18rem]">
            <x-ui.card as="ul" class="divide-y divide-line">
                @foreach ($order->items as $item)
                    <li class="flex items-center gap-4 p-4">
                        <div class="size-14 shrink-0 overflow-hidden rounded-md bg-media">
                            @if ($item->product?->thumbnail_url)
                                <img src="{{ $item->product->thumbnail_url }}" alt="" class="size-full object-cover" />
                            @else
                                <span class="flex size-full items-center justify-center text-brand-text"><span class="material-symbols-outlined">inventory_2</span></span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            @if ($item->product)
                                <a href="{{ route('shop.product', $item->product->slug) }}" class="line-clamp-2 font-medium text-zinc-900 hover:text-brand-text dark:text-white" wire:navigate>{{ $item->product_name_snapshot }}</a>
                            @else
                                <p class="line-clamp-2 font-medium text-zinc-900 dark:text-white">{{ $item->product_name_snapshot }}</p>
                            @endif
                            @if ($item->sku_snapshot)
                                <p class="text-xs text-zinc-400">{{ __('SKU') }}: {{ $item->sku_snapshot }}</p>
                            @endif
                            <p class="text-xs text-zinc-500">{{ $item->quantity }} × {{ formatMoney($item->unit_price) }}@if ($item->discount_percentage) · {{ __(':percent% off', ['percent' => $item->discount_percentage]) }}@endif @if ($item->offer_name_snapshot) · {{ __(':name (:percent% off)', ['name' => $item->offer_name_snapshot, 'percent' => $item->offer_discount_percentage]) }}@endif</p>
                        </div>
                        <span class="font-medium">{{ formatMoney($item->line_total) }}</span>
                    </li>
                @endforeach
            </x-ui.card>

            <x-ui.card as="aside" class="flex h-fit flex-col gap-5 p-5">
                <x-frontend.orders.summary :order="$order" />
                <dl class="space-y-3 border-t border-line pt-4 text-sm">
                    <x-backoffice.management.detail-item :label="__('Payment Status')">
                        <x-ui.badge :color="$order->payment_status->color()">{{ $order->payment_status->label() }}</x-ui.badge>
                    </x-backoffice.management.detail-item>
                    @if ($order->payment?->method)
                        <x-backoffice.management.detail-item :label="__('Paid With')">{{ $order->payment->method->label() }}</x-backoffice.management.detail-item>
                    @endif
                    @if ($order->payment?->transaction_id)
                        <x-backoffice.management.detail-item :label="__('Transaction ID')"><span class="font-mono">{{ $order->payment->transaction_id }}</span></x-backoffice.management.detail-item>
                    @endif
                    <x-backoffice.management.detail-item :label="__('Phone')">{{ $order->phone }}</x-backoffice.management.detail-item>
                    <x-backoffice.management.detail-item :label="__('Delivery Address')">{{ $order->shipping_address }}</x-backoffice.management.detail-item>
                    <x-backoffice.management.detail-item :label="__('Billing Address')">{{ $order->billing_address }}</x-backoffice.management.detail-item>
                </dl>
            </x-ui.card>
        </div>
    </x-frontend.layout.page>
</x-layouts::frontend>
