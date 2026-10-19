<div class="flex flex-col gap-6">
    <form wire:submit="track" class="grid gap-4 rounded-2xl shadow-card border border-zinc-200/80 bg-white p-5 sm:grid-cols-[1fr_1fr_auto] sm:items-end dark:border-zinc-800 dark:bg-zinc-900">
        <flux:input wire:model="orderId" :label="__('Order ID')" placeholder="NK-ABC123" required />
        <flux:input wire:model="email" type="email" :label="__('Email used for the order')" placeholder="email@example.com" required />
        <flux:button type="submit" variant="primary" icon="magnifying-glass">{{ __('Track') }}</flux:button>
    </form>

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
