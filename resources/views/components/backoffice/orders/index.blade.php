<x-backoffice.layout.main-content-container :title="__('Orders')" :subtitle="__('Contact customers, verify bKash and Nagad payments and unlock their books.')">
    @unless ($showOrder)
        <flux:error name="order" />
    @endunless

    <x-backoffice.management.table :sortable="$this->tableSortableColumns"
        :columns="[
            'public_order_id' => __('Order ID'),
            'customer' => __('Customer'),
            'total' => __('Total'),
            'payment_status' => __('Payment'),
            'transaction_id' => __('TrxID'),
            'created_at' => __('Placed'),
            'status' => __('Status'),
            'actions' => __('Action'),
        ]"
        :sort-by="$sortBy"
        :sort-direction="$sortDirection"
    >
        <x-slot:toolbar>
            <div class="flex flex-wrap items-end gap-2">
                <x-backoffice.management.per-page :placeholder="__('Select')" />
                <x-backoffice.management.filter model="status" :label="__('Status')" :options="$this->statusOptions" />
                <x-backoffice.management.filter model="paymentStatus" :label="__('Payment')" :options="$this->paymentStatusOptions" />
            </div>
            <div class="ms-auto">
                <x-backoffice.management.search model="search" :placeholder="__('Order ID, email, phone or TrxID...')" :label="__('Search orders')" class="max-w-xs" />
            </div>
        </x-slot:toolbar>

        <x-slot:rows>
            @forelse ($this->orders as $order)
                <flux:table.row wire:key="order-{{ $order->id }}">
                    <flux:table.cell class="font-mono font-medium">{{ $order->public_order_id }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="max-w-48 truncate">{{ $order->customer_name }}</div>
                        <div class="max-w-48 truncate text-xs text-zinc-500">{{ $order->phone }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ formatMoney($order->total) }}</div>
                        <div class="text-xs text-zinc-500">{{ trans_choice(':count item|:count items', $order->items_count, ['count' => $order->items_count]) }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$order->payment_status->color()">{{ $order->payment_status->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="font-mono text-xs">{{ $order->transaction_id ?? '-' }}</div>
                        <div class="text-xs text-zinc-500">{{ $order->payment_method?->label() }}</div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <span title="{{ formatDateTime($order->created_at) }}">{{ $order->created_at->diffForHumans() }}</span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            <flux:button size="xs" variant="filled" wire:click="viewDetails({{ $order->id }})">{{ __('Details') }}</flux:button>
                            @unless ($order->status->isCancelled())
                                <flux:button size="xs" variant="filled" color="sky" wire:click="viewOrder({{ $order->id }})">{{ __('Manage') }}</flux:button>
                            @endunless
                            @if ($order->has_invoice)
                                <flux:button size="xs" variant="filled" :href="route('manage.orders.invoice', $order)">{{ __('Invoice') }}</flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8">{{ __('No orders found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </x-slot:rows>
        <x-slot:pagination>
            <x-backoffice.management.pagination :paginator="$this->orders" />
        </x-slot:pagination>
    </x-backoffice.management.table>

    <x-backoffice.management.form-modal wire:model.live="showDetails" size="3xl" :show-default-footer="false" :title="__('Order :order', ['order' => $this->selectedOrder?->public_order_id])">
        @if ($order = $this->selectedOrder)
            <x-ui.tabs :tabs="['information' => __('Information'), 'items' => __('Items (:count)', ['count' => $order->items_count])]" wire:key="order-details-tabs-{{ $order->id }}">
                <x-ui.tab-panel name="information" class="space-y-6">
                    <x-backoffice.orders.customer-card :order="$order" />

                    @unless ($order->status->isCancelled())
                        <x-backoffice.orders.status-steps :steps="$order->status->steps()" />
                    @endunless

                    <dl class="grid gap-4 sm:grid-cols-2">
                        <x-backoffice.management.detail-item :label="__('Contact mode')">{{ $order->customer_contact_label }}</x-backoffice.management.detail-item>
                        <x-backoffice.management.detail-item :label="__('Completed')">{{ formatDateTime($order->completed_at) ?: '-' }} {{ $order->completedBy?->name }}</x-backoffice.management.detail-item>
                        <x-backoffice.management.detail-item :label="__('Last updated by')">{{ $order->lastUpdateLabel() }}</x-backoffice.management.detail-item>
                        @if ($order->shipping_address)
                            <x-backoffice.management.detail-item :label="__('Delivery address')">{{ $order->shipping_address }}</x-backoffice.management.detail-item>
                        @endif
                        @if ($order->notes)
                            <x-backoffice.management.detail-item :label="__('Customer note')">{{ $order->notes }}</x-backoffice.management.detail-item>
                        @endif
                    </dl>

                    <div class="space-y-2">
                        <flux:heading>{{ __('Internal note') }}</flux:heading>
                        <flux:text>{{ $order->internal_note ?? __('No note added.') }}</flux:text>
                    </div>
                </x-ui.tab-panel>

                <x-ui.tab-panel name="items">
                    <x-backoffice.orders.item-list :order="$order" />
                </x-ui.tab-panel>
            </x-ui.tabs>

            <x-slot:footer>
                <div class="flex flex-wrap justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                    @if ($order->has_invoice)
                        <flux:button icon="arrow-down-tray" :href="route('manage.orders.invoice', $order)">{{ __('Invoice') }}</flux:button>
                    @endif
                    @unless ($order->status->isCancelled())
                        <flux:button variant="filled" color="sky" wire:click="viewOrder({{ $order->id }})">{{ __('Manage') }}</flux:button>
                    @endunless
                </div>
            </x-slot:footer>
        @endif
    </x-backoffice.management.form-modal>

    <x-backoffice.management.form-modal wire:model.live="showOrder" size="3xl" submit="save" :title="__('Manage order :order', ['order' => $this->selectedOrder?->public_order_id])">
        @if ($order = $this->selectedOrder)
            <x-ui.tabs :tabs="['information' => __('Information'), 'items' => __('Items (:count)', ['count' => $order->items_count])]" wire:key="order-manage-tabs-{{ $order->id }}">
                <x-ui.tab-panel name="information" class="space-y-5">
                    <flux:error name="order" />

                    <x-backoffice.orders.customer-card :order="$order" :extra-discount="$this->extraDiscountPreview" />

                    <div class="grid gap-4 sm:grid-cols-[1fr_9rem_1.5fr]">
                        <flux:select wire:model.live="orderPaymentStatus" :label="__('Payment status')">
                            @foreach ($this->paymentStatusOptions as $value => $label)
                                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="paymentMethod" :label="__('Payment mode')">
                            @foreach ($this->paymentMethodOptions as $value => $label)
                                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input wire:model="trxId" :label="__('TrxID')" :placeholder="__('Transaction ID from the customer')" />
                    </div>

                    <flux:text class="text-xs">{{ __('Marking the payment as paid completes the order and unlocks the books in the customer\'s library.') }}</flux:text>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <flux:select wire:model="contactMode" :label="__('Contact mode')" :placeholder="__('Not yet')">
                            @foreach ($this->contactModeOptions as $value => $label)
                                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:select wire:model="orderStatus" :label="__('Order status')">
                            @foreach ($this->editableStatusOptions as $value => $option)
                                <flux:select.option :value="$value" :disabled="$option['disabled']">{{ $option['label'] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:input type="number" step="1" min="0" :max="shopMaxExtraDiscount()" wire:model.live.debounce.400ms="extraDiscountPercentage" :disabled="! $order->isExtraDiscountEditable()" :label="__('Extra discount (%)')" :placeholder="__('Max :max%', ['max' => shopMaxExtraDiscount()])" />
                    </div>

                    <flux:textarea wire:model="internalNote" :label="__('Internal note')" rows="2" :placeholder="__('Only visible to staff')" />
                </x-ui.tab-panel>

                <x-ui.tab-panel name="items">
                    <x-backoffice.orders.item-list :order="$order" />
                </x-ui.tab-panel>
            </x-ui.tabs>

            @if ($order->isCancellable())
                <x-slot:footerActions>
                    <flux:button type="button" variant="danger" class="w-full sm:w-auto" wire:click="$set('showCancelConfirmation', true)">{{ __('Cancel order') }}</flux:button>
                </x-slot:footerActions>
            @endif
        @endif
    </x-backoffice.management.form-modal>

    <x-backoffice.management.form-modal wire:model="showCancelConfirmation" size="md" :title="__('Cancel this order?')">
        <flux:text>{{ __('The order closes and any stock goes back. This cannot be undone.') }}</flux:text>
        <x-slot:footer>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button">{{ __('Close') }}</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="danger" wire:click="cancelOrder">{{ __('Cancel order') }}</flux:button>
            </div>
        </x-slot:footer>
    </x-backoffice.management.form-modal>
</x-backoffice.layout.main-content-container>
