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

<a href="{{ $href }}" wire:navigate {{ $attributes->class(['group flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3.5 transition hover:bg-brand-50/60 dark:hover:bg-zinc-800/60']) }}>
    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-300">
        <flux:icon.shopping-bag variant="outline" class="size-5" />
    </span>
    <div class="min-w-0 flex-1">
        <p class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ $number }}</p>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $date }} · {{ trans_choice(':count item|:count items', $itemsCount, ['count' => $itemsCount]) }}</p>
    </div>
    <div class="flex items-center gap-2">
        <x-ui.badge :color="$paymentColor">{{ $payment }}</x-ui.badge>
        <x-ui.badge :color="$statusColor">{{ $status }}</x-ui.badge>
    </div>
    <span class="w-24 text-end font-semibold text-zinc-900 dark:text-white">{{ formatMoney($total) }}</span>
    <flux:icon.chevron-right variant="mini" class="text-zinc-400 transition group-hover:translate-x-0.5 group-hover:text-brand-600" />
</a>
