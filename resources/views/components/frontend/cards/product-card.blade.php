@props([
    'productId',
    'href',
    'name',
    'author' => null,
    'coverUrl' => null,
    'price' => 0,
    'originalPrice' => null,
    'discount' => null,
    'free' => false,
    'owned' => false,
    'readable' => true,
    'badge' => null,
    'badgeColor' => 'amber',
    'available' => true,
])

<article x-data {{ $attributes->class(['group relative flex flex-col overflow-hidden rounded-2xl shadow-card border border-zinc-200/80 bg-white transition-colors hover:border-brand-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-brand-700']) }}>
    <a href="{{ $href }}" wire:navigate class="relative block aspect-[3/4] overflow-hidden bg-brand-50 dark:bg-brand-950">
        @if ($coverUrl)
            <img src="{{ $coverUrl }}" alt="{{ $name }}" loading="lazy" class="size-full object-cover" />
        @else
            <div class="flex size-full flex-col items-center justify-center gap-3 p-4 text-center text-brand-800 dark:text-brand-100">
                <span class="material-symbols-outlined text-5xl! text-brand-500">auto_stories</span>
                <span class="line-clamp-3 text-sm font-semibold">{{ $name }}</span>
            </div>
        @endif

        <div class="absolute start-2 end-2 top-2 flex flex-wrap items-center gap-1">
            @if ($free)
                <x-ui.badge color="green" solid>{{ __('Free') }}</x-ui.badge>
            @elseif ($discount)
                <x-ui.badge color="yellow" solid>−{{ $discount }}%</x-ui.badge>
            @endif
            @if ($badge)
                <x-ui.badge :color="$badgeColor" solid>{{ $badge }}</x-ui.badge>
            @endif
            @unless ($available)
                <x-ui.badge color="red" solid>{{ __('Out of Stock') }}</x-ui.badge>
            @endunless
        </div>
    </a>

    <div class="flex flex-1 flex-col gap-1 p-3 sm:p-4">
        <a href="{{ $href }}" wire:navigate class="line-clamp-2 font-semibold leading-snug text-zinc-900 hover:text-brand-700 dark:text-white dark:hover:text-brand-300">{{ $name }}</a>
        @if ($author)
            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $author }}</p>
        @endif

        <div class="mt-auto flex items-center justify-between gap-2 pt-3">
            <x-ui.price :amount="$price" :original="$originalPrice" :free="$free" size="sm" />

            @if ($free || $owned)
                @if ($readable)
                    <flux:button size="sm" variant="primary" icon="book-open" x-on:click="Livewire.dispatch('open-book', { bookId: {{ $productId }} })">
                        {{ __('Read') }}
                    </flux:button>
                @endif
            @else
                @if ($available)
                    <flux:button size="sm" variant="primary" square icon="shopping-cart" x-on:click="Livewire.dispatch('add-to-cart', { productId: {{ $productId }} })" :aria-label="__('Add to cart')" :tooltip="__('Add to cart')" />
                @else
                    <flux:button size="sm" square icon="shopping-cart" disabled :aria-label="__('Out of Stock')" />
                @endif
            @endif
        </div>
    </div>
</article>
