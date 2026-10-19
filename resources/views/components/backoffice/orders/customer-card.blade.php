@props([
    'order',
    'extraDiscount' => null,
])

<div {{ $attributes->class(['rounded-xl shadow-card border border-line bg-subtle']) }}>
    <div class="flex flex-wrap items-center gap-4 p-4">
        <x-ui.avatar :name="$order->customer_name" />
        <div class="min-w-0 flex-1">
            <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $order->customer_name }}</p>
            <p class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $order->customer_email }} · {{ $order->phone }}</p>
        </div>
        <div class="shrink-0 text-end">
            @if (($extraDiscount['amount'] ?? $order->extra_discount_amount) > 0)
                <dl class="mb-1 grid grid-cols-[auto_auto] justify-end gap-x-3 gap-y-0.5 text-xs">
                    <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Original total') }}</dt>
                    <dd class="font-medium text-zinc-700 dark:text-zinc-300">{{ formatMoney($order->total_before_extra_discount) }}</dd>
                    <dt class="text-zinc-600 dark:text-zinc-300">{{ __('Extra discount (:percent%)', ['percent' => $extraDiscount['percentage'] ?? $order->extra_discount_percentage]) }}</dt>
                    <dd class="font-medium text-zinc-800 dark:text-zinc-100">− {{ formatMoney($extraDiscount['amount'] ?? $order->extra_discount_amount) }}</dd>
                </dl>
                <p class="border-t border-line pt-1 text-lg font-semibold text-zinc-900 dark:text-white">{{ formatMoney($extraDiscount['total'] ?? $order->total) }}</p>
            @else
                <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ formatMoney($order->total) }}</p>
            @endif
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ trans_choice(':count item|:count items', (int) $order->items_sum_quantity, ['count' => (int) $order->items_sum_quantity]) }}</p>
        </div>
    </div>

    <dl class="grid grid-cols-2 gap-4 break-words px-4 pb-4 sm:grid-cols-3 lg:grid-cols-5">
        <x-backoffice.management.detail-item :label="__('Order Status')">
            <x-ui.badge :color="$order->status->color()">{{ $order->status->label() }}</x-ui.badge>
        </x-backoffice.management.detail-item>
        <x-backoffice.management.detail-item :label="__('Payment')">
            <x-ui.badge :color="$order->payment_status->color()">{{ $order->payment_status->label() }}</x-ui.badge>
        </x-backoffice.management.detail-item>
        <x-backoffice.management.detail-item :label="__('Paid With')">{{ $order->payment_method?->label() ?? appEmptyValue() }}</x-backoffice.management.detail-item>
        <x-backoffice.management.detail-item :label="__('Transaction ID')"><span class="font-mono">{{ $order->transaction_id ?? appEmptyValue() }}</span></x-backoffice.management.detail-item>
        <x-backoffice.management.detail-item :label="__('Placed')">{{ formatDateTime($order->created_at) }}</x-backoffice.management.detail-item>
    </dl>
</div>
