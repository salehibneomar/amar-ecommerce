@props([
    'items',
])

<ul {{ $attributes->class(['mb-4 space-y-3 border-b border-line pb-4 text-sm']) }}>
    @foreach ($items as $item)
        <li wire:key="checkout-item-{{ $item['product']->id }}" class="flex items-center gap-3">
            <div class="size-12 shrink-0 overflow-hidden rounded-md bg-media">
                @if ($item['product']->thumbnail_url)
                    <img src="{{ $item['product']->thumbnail_url }}" alt="" class="size-full object-cover" />
                @else
                    <span class="flex size-full items-center justify-center text-brand-text"><span class="material-symbols-outlined">inventory_2</span></span>
                @endif
            </div>
            <span class="grid min-w-0 flex-1">
                <span class="truncate text-zinc-700 dark:text-zinc-300">{{ $item['product']->name }} <span class="text-zinc-400">× {{ $item['quantity'] }}</span></span>
                <span class="truncate text-xs text-zinc-400">{{ __('SKU') }}: {{ $item['product']->sku }}</span>
            </span>
            <x-ui.price :amount="$item['line_total']" :original="$item['original_total']" size="sm" class="shrink-0" />
        </li>
    @endforeach
</ul>
