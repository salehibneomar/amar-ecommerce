<x-backoffice.layout.main-content-container :title="__('Orders')" :subtitle="__('Confirm, ship and complete customer orders.')">
    @unless ($showOrder)
        <flux:error name="order" />
    @endunless

    <x-backoffice.management.table :sortable="$this->tableSortableColumns"
        :columns="[
            'public_order_id' => __('Order ID'),
            'customer' => __('Customer'),
            'total' => __('Total'),
            'payment_status' => __('Payment'),
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
                <x-backoffice.management.search model="search" :placeholder="__('Order ID, email or phone...')" :label="__('Search Orders')" />
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
                        <x-ui.badge :color="$order->payment_status->color()">{{ $order->payment_status->label() }}</x-ui.badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <span title="{{ formatDateTime($order->created_at) }}">{{ $order->created_at->diffForHumans() }}</span>
                    </flux:table.cell>
                    <flux:table.cell>
                        <x-ui.badge :color="$order->status->color()">{{ $order->status->label() }}</x-ui.badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <x-backoffice.management.actions>
                            <x-backoffice.management.action icon="eye" :label="__('Details')" variant="filled" wire:click="viewDetails({{ $order->id }})" />
                            @unless ($order->status->isCancelled())
                                <x-backoffice.management.action icon="adjustments-horizontal" :label="__('Manage')" variant="filled" color="sky" wire:click="viewOrder({{ $order->id }})" />
                            @endunless
                            @if ($order->has_invoice)
                                <x-backoffice.management.action icon="document-arrow-down" :label="__('Invoice')" variant="filled" :href="route('manage.orders.invoice', $order)" />
                            @endif
                        </x-backoffice.management.actions>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">{{ __('No orders found.') }}</flux:table.cell>
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
                        @if ($this->isManualPayment)
                            <x-backoffice.management.detail-item :label="__('Contact Mode')">{{ $order->customer_contact_label }}</x-backoffice.management.detail-item>
                        @endif
                        <x-backoffice.management.detail-item :label="__('Completed')">{{ formatDateTime($order->completed_at) ?: appEmptyValue() }} {{ $order->completedBy?->name }}</x-backoffice.management.detail-item>
                        <x-backoffice.management.detail-item :label="__('Last Updated by')">{{ $order->lastUpdateLabel() }}</x-backoffice.management.detail-item>
                        <x-backoffice.management.detail-item :label="__('Delivery Address')">{{ $order->shipping_address }}</x-backoffice.management.detail-item>
                        <x-backoffice.management.detail-item :label="__('Billing Address')">{{ $order->billing_address }}</x-backoffice.management.detail-item>
                        @if ($order->notes)
                            <x-backoffice.management.detail-item :label="__('Customer Note')">{{ $order->notes }}</x-backoffice.management.detail-item>
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
                        <x-ui.button type="button">{{ __('Close') }}</x-ui.button>
                    </flux:modal.close>
                    @if ($order->has_invoice)
                        <x-ui.button icon="arrow-down-tray" :href="route('manage.orders.invoice', $order)">{{ __('Invoice') }}</x-ui.button>
                    @endif
                    @unless ($order->status->isCancelled())
                        <x-ui.button variant="filled" color="sky" wire:click="viewOrder({{ $order->id }})">{{ __('Manage') }}</x-ui.button>
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

                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-ui.select model="orderPaymentStatus" :label="__('Payment Status')" :options="$this->paymentStatusOptions" live />
                        @if ($this->isManualPayment)
                            <x-ui.select model="paymentMethod" :label="__('Payment Mode')" :options="$this->paymentMethodOptions" :placeholder="__('Select a payment mode')" />
                            <flux:input wire:model="trxId" :label="__('TrxID')" :placeholder="__('Transaction ID from the customer')" />
                            <x-ui.select model="contactMode" :label="__('Contact Mode')" :options="$this->contactModeOptions" :placeholder="__('Not yet')" />
                        @endif
                        <x-ui.select model="orderStatus" :label="__('Order Status')" :options="$this->editableStatusOptions" />
                        <flux:input type="number" step="1" min="0" :max="shopMaxExtraDiscount()" wire:model.live.debounce.400ms="extraDiscountPercentage" :disabled="! $order->isExtraDiscountEditable()" :label="__('Extra Discount (%)')" :placeholder="__('Max :max%', ['max' => shopMaxExtraDiscount()])" />
                    </div>

                    <flux:textarea wire:model="internalNote" :label="__('Internal Note')" rows="2" :placeholder="__('Only visible to staff')" />
                </x-ui.tab-panel>

                <x-ui.tab-panel name="items">
                    <x-backoffice.orders.item-list :order="$order" />
                </x-ui.tab-panel>
            </x-ui.tabs>

            @if ($order->isCancellable())
                <x-slot:footerActions>
                    <x-ui.button type="button" variant="danger" class="w-full sm:w-auto" wire:click="$set('showCancelConfirmation', true)">{{ __('Cancel Order') }}</x-ui.button>
                </x-slot:footerActions>
            @endif
        @endif
    </x-backoffice.management.form-modal>

    <x-backoffice.management.form-modal wire:model="showCancelConfirmation" size="md" :title="__('Cancel this order?')">
        <flux:text>{{ __('The order closes and any stock goes back. This cannot be undone.') }}</flux:text>
        <x-slot:footer>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <x-ui.button type="button">{{ __('Close') }}</x-ui.button>
                </flux:modal.close>
                <x-ui.button type="button" variant="danger" wire:click="cancelOrder">{{ __('Cancel Order') }}</x-ui.button>
            </div>
        </x-slot:footer>
    </x-backoffice.management.form-modal>
</x-backoffice.layout.main-content-container>
