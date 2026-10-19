<x-layouts::frontend :title="$product->name" :description="plainText($product->description)" :image="$product->cover_large_url" seo-type="product">
    <div class="mx-auto max-w-main px-4 pt-6 pb-8 sm:px-6 lg:pb-12">
        <nav class="mb-6 flex flex-wrap items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('shop') }}" class="hover:text-brand-text" wire:navigate>{{ __('Shop') }}</a>
            @foreach ($categoryTrail as $crumb)
                <flux:icon.chevron-right variant="micro" />
                <a href="{{ route('shop', ['category' => $crumb['id']]) }}" class="hover:text-brand-text" wire:navigate>{{ $crumb['name'] }}</a>
            @endforeach
        </nav>

        <div class="grid gap-8 md:grid-cols-[minmax(0,20rem)_1fr] lg:gap-14">
            <div class="mx-auto w-full max-w-xs md:max-w-none">
                <div class="relative aspect-[3/4] overflow-hidden rounded-xl bg-media ring-2 ring-zinc-900/5">
                    @if ($product->cover_preview_url)
                        <img src="{{ $product->cover_preview_url }}" alt="{{ $product->name }}" class="size-full object-cover" />
                        <flux:modal.trigger name="cover-viewer">
                            <x-ui.button size="sm" color="overlay" square icon="arrows-pointing-out" class="absolute end-3 top-3" :aria-label="__('View cover')" :tooltip="__('View cover')" />
                        </flux:modal.trigger>
                    @else
                        <div class="flex size-full flex-col items-center justify-center gap-4 p-6 text-center text-zinc-700 dark:text-zinc-200">
                            <span class="material-symbols-outlined text-7xl! text-brand-500">auto_stories</span>
                            <span class="text-lg font-semibold">{{ $product->name }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex flex-col gap-6">
                <div class="space-y-3">
                    <div class="flex flex-wrap gap-2">
                        @if ($product->badge)
                            <x-ui.badge :color="$product->badge->color()">{{ $product->badge->label() }}</x-ui.badge>
                        @endif
                        @unless ($product->status->isInStock())
                            <x-ui.badge :color="$product->status->color()">{{ $product->status->label() }}</x-ui.badge>
                        @endunless
                    </div>
                    <h1 class="text-3xl font-bold leading-tight tracking-tight text-zinc-900 sm:text-4xl dark:text-white">{{ $product->name }}</h1>
                    @if ($product->author)
                        <p class="text-lg text-zinc-600 dark:text-zinc-300">{{ __('by :author', ['author' => $product->author]) }}</p>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.price :amount="$product->final_price" :original="$product->has_discount ? $product->price : null" :free="$product->is_free" size="lg" />
                    @if ($product->has_discount)
                        <x-ui.badge color="yellow">{{ __(':percent% Off', ['percent' => $product->discount_percentage]) }}</x-ui.badge>
                    @endif
                </div>

                @if ($offer && ! $product->is_free && ! $ownsProduct)
                    <x-ui.banner tone="accent" class="flex items-center gap-3 text-zinc-800 dark:text-zinc-100">
                        <span class="material-symbols-outlined text-gold-600 dark:text-gold-400">local_offer</span>
                        <span><strong>{{ $offer->name }}:</strong> <x-frontend.content.offer-scope :offer="$offer->discountSummary()" with-percentage /></span>
                    </x-ui.banner>
                @endif

                <div x-data class="flex flex-wrap gap-3">
                    @if ($ownsProduct || $product->is_free)
                        @if ($product->is_digital)
                            <x-ui.button variant="primary" icon="book-open" x-on:click="Livewire.dispatch('open-book', { bookId: {{ $product->id }} })">{{ __('Read now') }}</x-ui.button>
                        @endif
                        @if ($ownsProduct)
                            <x-ui.button icon="check-badge" :href="route('user.library')" wire:navigate>{{ __('In Your Library') }}</x-ui.button>
                        @endif
                    @elseif ($product->status->isInStock())
                        <x-ui.button variant="primary" icon="bolt" x-on:click="Livewire.dispatch('add-to-cart', { productId: {{ $product->id }}, buyNow: true })">{{ __('Buy now') }}</x-ui.button>
                        <x-ui.button icon="shopping-cart" x-on:click="Livewire.dispatch('add-to-cart', { productId: {{ $product->id }} })">{{ __('Add to cart') }}</x-ui.button>
                        @if ($product->is_previewable)
                            <x-ui.button variant="ghost" icon="eye" x-on:click="Livewire.dispatch('open-book', { bookId: {{ $product->id }} })">{{ __('Read preview') }}</x-ui.button>
                        @endif
                    @endif
                </div>

                <x-ui.card as="dl" class="grid grid-cols-2 gap-4 p-5 text-sm sm:grid-cols-3">
                    @foreach ([
                        __('Author') => $product->author,
                        __('Pages') => $product->pages,
                        __('Category') => $product->category?->name,
                    ] as $label => $value)
                        @if (filled($value))
                            <div>
                                <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                                <dd class="mt-0.5 font-medium text-zinc-900 dark:text-white">{{ $value }}</dd>
                            </div>
                        @endif
                    @endforeach
                </x-ui.card>

                @if ($product->description)
                    <div>
                        <h2 class="mb-2 text-lg font-semibold text-zinc-900 dark:text-white">{{ __('About this book') }}</h2>
                        <div class="rich-text leading-relaxed text-zinc-700 dark:text-zinc-300">{!! $product->description !!}</div>
                    </div>
                @endif
            </div>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <section class="mt-16">
                <h2 class="mb-6 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ __('More in this category') }}</h2>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    @foreach ($relatedProducts as $related)
                        <x-frontend.cards.product-tile :product="$related" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    @if ($product->cover_large_url)
        <x-media.image-viewer name="cover-viewer" :src="$product->cover_large_url" :alt="$product->name" :title="$product->name" />
    @endif

    <livewire:frontend.reader.index />
</x-layouts::frontend>
