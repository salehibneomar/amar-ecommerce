@props([
    'href',
    'number',
    'date',
    'total',
    'itemsCount' => 0,
    'status',
    'statusColor' => 'zinc',
    'payment',
    'paymentColor' => 'zinc',
])

<x-ui.list-item :href="$href" {{ $attributes->class('flex-wrap gap-x-4 gap-y-2') }}>
    <x-slot:leading>
        <x-ui.icon-badge>
            <flux:icon.shopping-bag variant="outline" class="size-5" />
        </x-ui.icon-badge>
    </x-slot:leading>
    <p class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ $number }}</p>
    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $date }} · {{ trans_choice(':count item|:count items', $itemsCount, ['count' => $itemsCount]) }}</p>
    <x-slot:trailing>
        <div class="flex items-center gap-2">
            <x-ui.badge :color="$paymentColor">{{ $payment }}</x-ui.badge>
            <x-ui.badge :color="$statusColor">{{ $status }}</x-ui.badge>
        </div>
        <span class="w-24 text-end font-semibold text-zinc-900 dark:text-white">{{ formatMoney($total) }}</span>
    </x-slot:trailing>
</x-ui.list-item>
