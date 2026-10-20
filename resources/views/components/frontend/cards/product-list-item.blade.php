@props([
    'product',
    'meta' => null,
    'note' => null,
    'as' => 'a',
])

<x-ui.card :as="$as" :interactive="$as !== 'div'" {{ $attributes->merge($as === 'a' ? ['href' => route('shop.product', $product->slug), 'wire:navigate' => true] : [])->class(['group flex w-full min-w-0 items-center gap-3 p-2.5 text-start', 'cursor-pointer' => $as !== 'div']) }}>
    <div class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-sm bg-media text-brand-500">
        @if ($product->thumbnail_url)
            <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover" loading="lazy" />
        @else
            <span class="material-symbols-outlined text-xl opacity-80">inventory_2</span>
        @endif
    </div>

    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $product->name }}</p>
        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $meta ?? ($product->status->isInStock() ? formatMoney($product->final_price) : __('Out of Stock')) }}</p>
        @if ($note)
            <p class="mt-0.5 line-clamp-2 text-xs text-zinc-600 dark:text-zinc-300">{{ $note }}</p>
        @endif
    </div>

    @if ($as !== 'div')
        <span class="material-symbols-outlined text-xl text-brand-text transition-transform group-hover:translate-x-0.5">chevron_right</span>
    @endif
</x-ui.card>
