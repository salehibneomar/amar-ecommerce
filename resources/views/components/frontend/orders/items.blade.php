@props([
    'order',
])

<x-ui.card as="section" {{ $attributes->class('p-5') }}>
    <ul class="mb-4 space-y-2 border-b border-line pb-4 text-sm">
        @foreach ($order->items as $item)
            <li class="flex justify-between gap-3">
                <span class="min-w-0 truncate text-zinc-700 dark:text-zinc-300">{{ $item->product_name_snapshot }} <span class="text-zinc-400">× {{ $item->quantity }}</span></span>
                <x-ui.price :amount="$item->line_total" :original="$item->original_total" size="sm" class="shrink-0" />
            </li>
        @endforeach
    </ul>
    <x-frontend.orders.summary :order="$order" />
</x-ui.card>
