<x-layouts::frontend :title="__('My orders')">
    <x-frontend.layout.page :title="__('My orders')">
        @if ($orders->isEmpty())
            <div class="flex flex-col items-center gap-4 rounded-2xl border border-dashed border-zinc-300 px-6 py-16 text-center dark:border-zinc-700">
                <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('No orders yet') }}</p>
                <flux:button variant="primary" :href="route('shop')" wire:navigate>{{ __('Shop') }}</flux:button>
            </div>
        @else
            <div class="divide-y divide-zinc-200 overflow-hidden rounded-2xl shadow-card border border-zinc-200/80 bg-white dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900">
                @foreach ($orders as $order)
                    <x-frontend.orders.row
                        :href="route('user.orders.detail', $order->public_order_id)"
                        :number="$order->public_order_id"
                        :date="$order->created_at->format('d M Y')"
                        :total="$order->total"
                        :items-count="$order->items_count"
                        :status="$order->status->label()" :status-color="$order->status->color()"
                        :payment="$order->payment_status->label()" :payment-color="$order->payment_status->color()" />
                @endforeach
            </div>
            {{ $orders->links() }}
        @endif
    </x-frontend.layout.page>
</x-layouts::frontend>
