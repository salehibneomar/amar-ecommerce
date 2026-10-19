@props([
    'order',
    'extraDiscount' => null,
])

<div {{ $attributes->class(['rounded-xl shadow-card border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50']) }}>
    <div class="flex flex-wrap items-center gap-4 p-4">
        <flux:avatar :name="$order->customer_name" />
        <div class="min-w-0 flex-1">
            <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $order->customer_name }}</p>
            <p class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $order->customer_email }} · {{ $order->phone }}</p>
        </div>
        <div class="shrink-0 text-end">
            @if (($extraDiscount['amount'] ?? $order->extra_discount_amount) > 0)
                <dl class="mb-1 grid grid-cols-[auto_auto] justify-end gap-x-3 gap-y-0.5 text-xs">
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Original total') }}</dt>
                    <dd class="font-medium text-zinc-700 dark:text-zinc-300">{{ formatMoney($order->total_before_extra_discount) }}</dd>
                    <dt class="text-brand-700 dark:text-brand-300">{{ __('Extra discount (:percent%)', ['percent' => $extraDiscount['percentage'] ?? $order->extra_discount_percentage]) }}</dt>
                    <dd class="font-medium text-brand-700 dark:text-brand-300">− {{ formatMoney($extraDiscount['amount'] ?? $order->extra_discount_amount) }}</dd>
                </dl>
                <p class="border-t border-zinc-200 pt-1 text-lg font-semibold text-zinc-900 dark:border-zinc-700 dark:text-white">{{ formatMoney($extraDiscount['total'] ?? $order->total) }}</p>
            @else
                <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ formatMoney($order->total) }}</p>
            @endif
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ trans_choice(':count item|:count items', (int) $order->items_sum_quantity, ['count' => (int) $order->items_sum_quantity]) }}</p>
        </div>
    </div>

    <dl class="grid grid-cols-2 gap-4 break-words px-4 pb-4 sm:grid-cols-3 lg:grid-cols-5">
        <x-backoffice.management.detail-item :label="__('Order status')">
            <flux:badge size="sm" :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
        </x-backoffice.management.detail-item>
        <x-backoffice.management.detail-item :label="__('Payment')">
            <flux:badge size="sm" :color="$order->payment_status->color()">{{ $order->payment_status->label() }}</flux:badge>
        </x-backoffice.management.detail-item>
        <x-backoffice.management.detail-item :label="__('Paid with')">{{ $order->payment_method?->label() ?? '-' }}</x-backoffice.management.detail-item>
        <x-backoffice.management.detail-item :label="__('Transaction ID')"><span class="font-mono">{{ $order->transaction_id ?? '-' }}</span></x-backoffice.management.detail-item>
        <x-backoffice.management.detail-item :label="__('Placed')">{{ formatDateTime($order->created_at) }}</x-backoffice.management.detail-item>
    </dl>
</div>
