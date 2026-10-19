<x-layouts::frontend :title="__('Order Details')">
    <x-frontend.layout.page :title="__('Order :order', ['order' => $order->public_order_id])" :subtitle="__('Placed on :date', ['date' => formatDateTime($order->created_at)])">
        <x-slot:actions>
            <div class="flex gap-2">
                <x-ui.button variant="ghost" icon="arrow-left" :href="route('user.orders')" wire:navigate>{{ __('All Orders') }}</x-ui.button>
                @if ($order->has_invoice)
                    <x-ui.button icon="arrow-down-tray" :href="route('user.orders.invoice', $order->public_order_id)">{{ __('Invoice') }}</x-ui.button>
                @endif
            </div>
        </x-slot:actions>

        <x-frontend.orders.status :order="$order" :contact-links="$contactLinks" />

        <div class="grid gap-6 md:grid-cols-[1fr_18rem]">
            <x-ui.card as="ul" class="divide-y divide-line">
                @foreach ($order->items as $item)
                    <li class="flex items-center gap-4 p-4">
                        <div class="h-16 w-12 shrink-0 overflow-hidden rounded-md bg-media">
                            @if ($item->product?->cover_thumbnail_url)
                                <img src="{{ $item->product->cover_thumbnail_url }}" alt="" class="size-full object-cover" />
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            @if ($item->product)
                                <a href="{{ route('shop.product', $item->product->slug) }}" class="line-clamp-2 font-medium text-zinc-900 hover:text-brand-text dark:text-white" wire:navigate>{{ $item->product_name_snapshot }}</a>
                            @else
                                <p class="line-clamp-2 font-medium text-zinc-900 dark:text-white">{{ $item->product_name_snapshot }}</p>
                            @endif
                            <p class="text-xs text-zinc-500">{{ $item->quantity }} × {{ formatMoney($item->unit_price) }}@if ($item->discount_percentage) · {{ __(':percent% off', ['percent' => $item->discount_percentage]) }}@endif @if ($item->offer_name_snapshot) · {{ __(':name (:percent% off)', ['name' => $item->offer_name_snapshot, 'percent' => $item->offer_discount_percentage]) }}@endif</p>
                        </div>
                        <div x-data class="flex flex-col items-end gap-2">
                            <span class="font-medium">{{ formatMoney($item->line_total) }}</span>
                            @if ($order->has_invoice && $item->product?->has_file)
                                <x-ui.button size="xs" variant="primary" icon="book-open" x-on:click="Livewire.dispatch('open-book', { bookId: {{ $item->product->id }} })">{{ __('Read') }}</x-ui.button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </x-ui.card>

            <x-ui.card as="aside" class="flex h-fit flex-col gap-5 p-5">
                <x-frontend.orders.summary :order="$order" />
                <dl class="space-y-3 border-t border-line pt-4 text-sm">
                    <x-backoffice.management.detail-item :label="__('Payment Status')">
                        <x-ui.badge :color="$order->payment_status->color()">{{ $order->payment_status->label() }}</x-ui.badge>
                    </x-backoffice.management.detail-item>
                    @if ($order->payment_method)
                        <x-backoffice.management.detail-item :label="__('Paid With')">{{ $order->payment_method->label() }}</x-backoffice.management.detail-item>
                    @endif
                    @if ($order->transaction_id)
                        <x-backoffice.management.detail-item :label="__('Transaction ID')"><span class="font-mono">{{ $order->transaction_id }}</span></x-backoffice.management.detail-item>
                    @endif
                    <x-backoffice.management.detail-item :label="__('Phone')">{{ $order->phone }}</x-backoffice.management.detail-item>
                    @if ($order->shipping_address)
                        <x-backoffice.management.detail-item :label="__('Delivery Address')">{{ $order->shipping_address }}</x-backoffice.management.detail-item>
                    @endif
                </dl>
            </x-ui.card>
        </div>
    </x-frontend.layout.page>

    <livewire:frontend.reader.index />
</x-layouts::frontend>
