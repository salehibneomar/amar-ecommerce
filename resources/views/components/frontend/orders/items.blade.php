@props([
    'order',
])

<section {{ $attributes->class(['rounded-2xl shadow-card border border-zinc-200/80 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900']) }}>
    <ul class="mb-4 space-y-2 border-b border-zinc-200 pb-4 text-sm dark:border-zinc-700">
        @foreach ($order->items as $item)
            <li class="flex justify-between gap-3">
                <span class="min-w-0 truncate text-zinc-700 dark:text-zinc-300">{{ $item->product_name_snapshot }} <span class="text-zinc-400">× {{ $item->quantity }}</span></span>
                <x-ui.price :amount="$item->line_total" :original="$item->original_total" size="sm" class="shrink-0" />
            </li>
        @endforeach
    </ul>
    <x-frontend.orders.summary :order="$order" />
</section>
