@props([
    'order',
])

<x-ui.card as="section" {{ $attributes->class('p-5') }}>
    <ul class="mb-4 space-y-3 border-b border-line pb-4 text-sm">
        @foreach ($order->items as $item)
            <li class="flex items-center gap-3">
                <div class="size-12 shrink-0 overflow-hidden rounded-md bg-media">
                    @if ($item->product?->thumbnail_url)
                        <img src="{{ $item->product->thumbnail_url }}" alt="" class="size-full object-cover" />
                    @else
                        <span class="flex size-full items-center justify-center text-brand-text"><span class="material-symbols-outlined">inventory_2</span></span>
                    @endif
                </div>
                <span class="grid min-w-0 flex-1">
                    <span class="truncate text-zinc-700 dark:text-zinc-300">{{ $item->product_name_snapshot }} <span class="text-zinc-400">× {{ $item->quantity }}</span></span>
                    @if ($item->sku_snapshot)
                        <span class="truncate text-xs text-zinc-400">{{ __('SKU') }}: {{ $item->sku_snapshot }}</span>
                    @endif
                </span>
                <x-ui.price :amount="$item->line_total" :original="$item->original_total" size="sm" class="shrink-0" />
            </li>
        @endforeach
    </ul>
    <x-frontend.orders.summary :order="$order" />
</x-ui.card>
