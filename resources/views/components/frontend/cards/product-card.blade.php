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

<x-frontend.cards.card as="article" x-data interactive :padded="false" {{ $attributes->class('relative flex flex-col p-2') }}>
    <a href="{{ $href }}" wire:navigate class="relative block aspect-[3/4] overflow-hidden rounded-lg bg-media">
        @if ($coverUrl)
            <img src="{{ $coverUrl }}" alt="{{ $name }}" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105" />
        @else
            <div class="flex size-full flex-col items-center justify-center gap-3 p-4 text-center text-zinc-700 dark:text-zinc-200">
                <x-ui.icon-badge size="xl">
                    <span class="material-symbols-outlined text-3xl!">auto_stories</span>
                </x-ui.icon-badge>
                <span class="line-clamp-2 text-sm font-semibold">{{ $name }}</span>
            </div>
        @endif

        <div class="absolute start-2 end-2 top-2 flex flex-wrap items-center gap-1">
            @if ($free)
                <x-ui.badge color="green" overlay>{{ __('Free') }}</x-ui.badge>
            @elseif ($discount)
                <x-ui.badge color="amber" overlay>−{{ $discount }}%</x-ui.badge>
            @endif
            @if ($badge)
                <x-ui.badge :color="$badgeColor" overlay>{{ $badge }}</x-ui.badge>
            @endif
            @unless ($available)
                <x-ui.badge color="red" overlay>{{ __('Out of Stock') }}</x-ui.badge>
            @endunless
        </div>
    </a>

    <div class="flex flex-1 flex-col gap-1 px-1.5 pt-3 pb-1">
        <a href="{{ $href }}" wire:navigate class="line-clamp-2 font-semibold leading-snug text-zinc-900 hover:text-brand-text dark:text-white">{{ $name }}</a>
        @if ($author)
            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $author }}</p>
        @endif

        <div class="mt-auto flex items-center justify-between gap-2 pt-3">
            <x-ui.price :amount="$price" :original="$originalPrice" :free="$free" size="sm" />

            @if ($free || $owned)
                @if ($readable)
                    <x-ui.button size="sm" variant="primary" icon="book-open" x-on:click="Livewire.dispatch('open-book', { bookId: {{ $productId }} })">
                        {{ __('Read') }}
                    </x-ui.button>
                @endif
            @else
                @if ($available)
                    <x-ui.button size="sm" variant="primary" square icon="shopping-cart" x-on:click="Livewire.dispatch('add-to-cart', { productId: {{ $productId }} })" :aria-label="__('Add to cart')" :tooltip="__('Add to cart')" />
                @else
                    <x-ui.button size="sm" square icon="shopping-cart" disabled :aria-label="__('Out of Stock')" />
                @endif
            @endif
        </div>
    </div>
</x-frontend.cards.card>
