<x-layouts::frontend :title="__('Home')" landing>
    <section class="relative overflow-hidden">
        <div class="relative mx-auto max-w-main px-4 pt-6 pb-10 sm:px-6 lg:pt-8">
            @if (count($slides))
                <x-frontend.content.banner-slider :slides="$slides" />
            @else
                <div class="grid items-center gap-10 py-10 lg:grid-cols-2 lg:py-16">
                    <div class="space-y-6">
                        <h1 class="text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">{{ __('Knowledge that grows with every harvest') }}</h1>
                        <p class="max-w-xl text-lg text-zinc-600 dark:text-zinc-300">{{ __('Bangla books on farming, livestock, fisheries and more. Buy once, read instantly on any device.') }}</p>
                        <x-ui.button variant="primary" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('Browse Books') }}</x-ui.button>
                    </div>
                    <x-brand.app-logo-icon class="mx-auto w-full max-w-md" />
                </div>
            @endif

            <div class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                @foreach ([
                    ['bolt', __('Instant access'), __('Read right after payment')],
                    ['devices', __('Any device'), __('Phone, tablet or computer')],
                    ['visibility', __('Free preview'), $isPhysicalOnly ? __('Sample pages before you order') : __('First :pages pages of every book', ['pages' => bookPreviewPages()])],
                    ['payments', __('bKash & Nagad'), __('Simple local payment')],
                ] as [$icon, $title, $text])
                    <x-frontend.cards.card :padded="false" class="flex items-center gap-3 p-3">
                        <x-ui.icon-badge>
                            <span class="material-symbols-outlined">{{ $icon }}</span>
                        </x-ui.icon-badge>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $title }}</p>
                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $text }}</p>
                        </div>
                    </x-frontend.cards.card>
                @endforeach
            </div>
        </div>
    </section>

    @if (count($categories))
        <x-frontend.layout.section id="categories" :title="__('Browse by category')" width="max-w-main" muted class="[&>div]:pt-12 [&>div]:pb-16">
            <div class="-mt-8 mb-5 flex justify-center">
                <x-ui.button variant="ghost" size="sm" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('View All') }}</x-ui.button>
            </div>

            <div class="flex flex-wrap justify-center gap-3 sm:gap-4">
                @foreach ($categories as $category)
                    <x-frontend.cards.category-card :href="route('shop', ['category' => $category['id']])" :name="$category['name']" :image-url="$category['image_url']" class="w-[calc((100%-1.5rem)/3)] sm:w-28 md:w-32 lg:w-36" />
                @endforeach
            </div>
        </x-frontend.layout.section>
    @endif

    @if ($offer)
        <x-frontend.layout.section width="max-w-main" muted class="[&>div]:pt-0 [&>div]:pb-14">
            <x-frontend.content.offer-banner :offer="$offer" />
        </x-frontend.layout.section>
    @endif

    @if ($featuredProducts->isNotEmpty())
        <x-frontend.layout.section id="featured" :title="__('Featured books')" :description="__('Hand-picked by our team')" width="max-w-main" class="[&>div]:py-14">
            <x-slot:actions>
                <x-ui.button variant="ghost" size="sm" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('View All') }}</x-ui.button>
            </x-slot:actions>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($featuredProducts as $product)
                    <x-frontend.cards.product-tile :product="$product" :owned="in_array($product->id, $ownedIds, true)" />
                @endforeach
            </div>
        </x-frontend.layout.section>
    @endif

    @if ($newArrivals->isNotEmpty())
        <x-frontend.layout.section id="new" :title="__('New arrivals')" :description="__(':count books and growing', ['count' => number_format($booksCount)])" width="max-w-main" muted class="[&>div]:py-14">
            <x-slot:actions>
                <x-ui.button variant="ghost" size="sm" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('View All') }}</x-ui.button>
            </x-slot:actions>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($newArrivals as $product)
                    <x-frontend.cards.product-tile :product="$product" :owned="in_array($product->id, $ownedIds, true)" />
                @endforeach
            </div>
        </x-frontend.layout.section>
    @endif

    <x-frontend.layout.section id="how" :title="__('How to buy')" :description="__('Three simple steps, no card needed.')" class="[&>div]:py-16">
        <div class="grid gap-5 md:grid-cols-3">
            <x-frontend.cards.feature-card icon="shopping_cart" :title="__('1. Place your order')" :description="__('Pick your books and check out, no account needed. Read a free preview first if you like.')" />
            <x-frontend.cards.feature-card icon="send_money" :title="__('2. Pay with bKash or Nagad')" :description="__('Contact us with your order ID and we share the payment details.')" />
            <x-frontend.cards.feature-card icon="local_library" :title="__('3. Start reading')" :description="__('Once verified, your books unlock in My library.')" />
        </div>
    </x-frontend.layout.section>

    <section class="mx-auto max-w-main px-4 pb-20 sm:px-6">
        <x-ui.banner tone="brand" size="none" class="relative overflow-hidden rounded-2xl px-6 py-12 text-center sm:px-12">
            <h2 class="relative text-3xl font-bold text-zinc-900 dark:text-white">{{ __('Grow your knowledge, grow your harvest') }}</h2>
            <p class="relative mx-auto mt-3 max-w-xl text-zinc-600 dark:text-zinc-300">{{ __('Create a free account to read free books and keep your library on every device.') }}</p>
            <div class="relative mt-6 flex flex-wrap justify-center gap-3">
                @guest
                    <x-ui.button variant="primary" :href="route('user.register')" wire:navigate>{{ __('Create Free Account') }}</x-ui.button>
                @endguest
                <x-ui.button icon="building-storefront" :href="route('shop')" wire:navigate>{{ __('Shop') }}</x-ui.button>
            </div>
        </x-ui.banner>
    </section>

    <livewire:frontend.reader.index />
</x-layouts::frontend>
