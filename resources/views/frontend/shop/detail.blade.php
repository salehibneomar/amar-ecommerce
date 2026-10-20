<x-layouts::frontend :title="$product->name" :description="plainText($product->description)" :image="$product->large_image_url" seo-type="product">
    <div class="mx-auto max-w-main px-4 pt-6 pb-8 sm:px-6 lg:pb-12">
        <nav class="mb-6 flex flex-wrap items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ route('shop') }}" class="hover:text-brand-text" wire:navigate>{{ __('Shop') }}</a>
            @foreach ($categoryTrail as $crumb)
                <flux:icon.chevron-right class="size-4" />
                <a href="{{ route('shop', ['category' => $crumb['id']]) }}" class="hover:text-brand-text" wire:navigate>{{ $crumb['name'] }}</a>
            @endforeach
        </nav>

        <div class="grid gap-8 md:grid-cols-[minmax(0,26rem)_1fr] lg:gap-14">
            <x-media.gallery :images="$product->gallery_images" :alt="$product->name" name="product-image-viewer" icon="inventory_2" class="mx-auto w-full max-w-md md:max-w-none" />

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
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.price :amount="$product->final_price" :original="$product->has_discount ? $product->price : null" size="lg" />
                    @if ($product->has_discount)
                        <x-ui.badge color="yellow">{{ __(':percent% Off', ['percent' => $product->discount_percentage]) }}</x-ui.badge>
                    @endif
                </div>

                @if ($offer)
                    <x-ui.banner tone="accent" class="flex items-center gap-3 text-zinc-800 dark:text-zinc-100">
                        <span class="material-symbols-outlined text-gold-600 dark:text-gold-400">local_offer</span>
                        <span><strong>{{ $offer->name }}:</strong> <x-frontend.content.offer-scope :offer="$offer->discountSummary()" with-percentage /></span>
                    </x-ui.banner>
                @endif

                @if ($product->status->isInStock())
                    <div x-data class="flex flex-wrap gap-3">
                        <x-ui.button variant="primary" icon="bolt" x-on:click="Livewire.dispatch('add-to-cart', { productId: {{ $product->id }}, buyNow: true })">{{ __('Buy now') }}</x-ui.button>
                        <x-ui.button icon="shopping-cart" x-on:click="Livewire.dispatch('add-to-cart', { productId: {{ $product->id }} })">{{ __('Add to cart') }}</x-ui.button>
                    </div>
                @endif

                <x-ui.card as="dl" class="grid grid-cols-2 gap-4 p-5 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('SKU') }}</dt>
                        <dd class="mt-0.5 font-medium text-zinc-900 dark:text-white">{{ $product->sku }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Category') }}</dt>
                        <dd class="mt-0.5 font-medium text-zinc-900 dark:text-white">{{ $product->category?->name ?? appEmptyValue() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Availability') }}</dt>
                        <dd class="mt-0.5 font-medium text-zinc-900 dark:text-white">{{ $product->status->label() }}</dd>
                    </div>
                </x-ui.card>

                @if ($product->description)
                    <div>
                        <h2 class="mb-2 text-lg font-semibold text-zinc-900 dark:text-white">{{ __('About this product') }}</h2>
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

</x-layouts::frontend>
