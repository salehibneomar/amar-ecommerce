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
                        <flux:button variant="primary" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('Browse books') }}</flux:button>
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
                    <div class="flex items-center gap-3 rounded-2xl shadow-card border border-zinc-200/80 bg-white/80 p-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/80">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-300">
                            <span class="material-symbols-outlined">{{ $icon }}</span>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $title }}</p>
                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @if (count($categories))
        <x-frontend.layout.section id="categories" :title="__('Browse by category')" width="max-w-main" muted class="[&>div]:pt-12 [&>div]:pb-16">
            <div class="-mt-8 mb-5 flex justify-center">
                <flux:button variant="ghost" size="sm" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('View all') }}</flux:button>
            </div>

            <div class="flex flex-wrap justify-center gap-4 sm:gap-6">
                @foreach ($categories as $category)
                    <a href="{{ route('shop', ['category' => $category['id']]) }}" wire:navigate class="group w-[calc((100%-2rem)/3)] text-center sm:w-32 lg:w-40">
                        <div class="aspect-square overflow-hidden rounded-2xl border border-zinc-200/80 bg-brand-50 transition group-hover:border-brand-300 dark:border-zinc-800 dark:bg-brand-950 dark:group-hover:border-brand-700">
                            @if ($category['image_url'])
                                <img src="{{ $category['image_url'] }}" alt="{{ $category['name'] }}" width="400" height="400" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105" />
                            @else
                                <div class="flex size-full items-center justify-center">
                                    <span class="material-symbols-outlined text-4xl! text-brand-500/40">eco</span>
                                </div>
                            @endif
                        </div>
                        <p class="mt-2 line-clamp-2 text-sm font-medium text-zinc-800 group-hover:text-brand-700 dark:text-zinc-100 dark:group-hover:text-brand-300">{{ $category['name'] }}</p>
                    </a>
                @endforeach
            </div>
        </x-frontend.layout.section>
    @endif

    @if ($offer)
        <section class="bg-subtle">
            <div class="mx-auto max-w-main px-4 pb-12 sm:px-6">
                <div class="relative flex flex-col items-start gap-4 overflow-hidden rounded-3xl border border-gold-200 bg-gold-50 px-6 py-8 sm:flex-row dark:border-gold-800/50 dark:bg-gold-900/20 sm:items-center sm:justify-between sm:px-10">
                    <span class="material-symbols-outlined pointer-events-none absolute -end-6 -top-6 text-[10rem]! text-gold-200/60 dark:text-gold-800/30">local_offer</span>
                    <div class="relative">
                        <p class="text-sm font-semibold uppercase tracking-wider text-gold-700 dark:text-gold-300">{{ $offer->name }}</p>
                        <p class="mt-1 text-3xl font-bold text-zinc-900 sm:text-4xl dark:text-white">{{ $offer->headline }}</p>
                        @if ($offer->description || $offer->ends_at)
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $offer->description }}
                                @if ($offer->ends_at)
                                    · <x-frontend.content.countdown :to="$offer->ends_at" />
                                @endif
                            </p>
                        @endif
                    </div>
                    <flux:button icon-trailing="arrow-right" class="relative shrink-0" :href="route('shop')" wire:navigate>{{ __('Shop the offer') }}</flux:button>
                </div>
            </div>
        </section>
    @endif

    @if ($featuredProducts->isNotEmpty())
        <x-frontend.layout.section id="featured" :title="__('Featured books')" :description="__('Hand-picked by our team')" width="max-w-main" class="[&>div]:py-14">
            <x-slot:actions>
                <flux:button variant="ghost" size="sm" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('View all') }}</flux:button>
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
                <flux:button variant="ghost" size="sm" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('View all') }}</flux:button>
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
        <div class="relative overflow-hidden rounded-3xl border border-brand-100 bg-brand-50 px-6 py-12 text-center sm:px-12 dark:border-brand-900 dark:bg-brand-950/60">
            <h2 class="relative text-3xl font-bold text-zinc-900 dark:text-white">{{ __('Grow your knowledge, grow your harvest') }}</h2>
            <p class="relative mx-auto mt-3 max-w-xl text-zinc-600 dark:text-zinc-300">{{ __('Create a free account to read free books and keep your library on every device.') }}</p>
            <div class="relative mt-6 flex flex-wrap justify-center gap-3">
                @guest
                    <flux:button variant="primary" :href="route('user.register')" wire:navigate>{{ __('Create free account') }}</flux:button>
                @endguest
                <flux:button icon="building-storefront" :href="route('shop')" wire:navigate>{{ __('Shop') }}</flux:button>
            </div>
        </div>
    </section>

    <livewire:frontend.reader.index />
</x-layouts::frontend>
