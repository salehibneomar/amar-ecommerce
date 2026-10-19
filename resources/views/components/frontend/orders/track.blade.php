<div class="flex flex-col gap-6">
    <x-ui.card as="form" wire:submit="track" class="grid gap-4 p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <flux:input wire:model="orderId" :label="__('Order ID')" :placeholder="shopOrderPrefix().'-ABC123'" required />
        <flux:input wire:model="email" type="email" :label="__('Email Used for the Order')" placeholder="email@example.com" required />
        <x-ui.button type="submit" variant="primary" icon="magnifying-glass">{{ __('Track') }}</x-ui.button>
    </x-ui.card>

    @if ($order = $this->order)
        <div class="flex flex-wrap items-center justify-between gap-2">
            <flux:heading size="lg">{{ __('Order :order', ['order' => $order->public_order_id]) }}</flux:heading>
            <div class="flex gap-2">
                <x-ui.badge :color="$order->status->color()">{{ $order->status->label() }}</x-ui.badge>
                <x-ui.badge :color="$order->payment_status->color()">{{ $order->payment_status->label() }}</x-ui.badge>
            </div>
        </div>
        <x-frontend.orders.status :order="$order" :contact-links="$this->contactLinks" />
        <x-frontend.orders.items :order="$order" />
    @endif
</div>
