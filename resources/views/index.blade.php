<x-layouts.frontend landing>
    <section class="relative overflow-hidden">
        <div class="relative mx-auto max-w-main px-4 pt-6 pb-10 sm:px-6 lg:pt-8">
            @if (count($slides))
                <x-frontend.content.banner-slider :slides="$slides" />
            @else
                <div class="grid items-center gap-10 overflow-hidden rounded-2xl bg-hero px-6 py-10 sm:px-12 lg:grid-cols-2 lg:py-16">
                    <div class="space-y-6">
                        <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl">{{ __('Everything you need, delivered to your door') }}</h1>
                        <p class="max-w-xl text-lg text-white/85">{{ __('Quality products at fair prices. Order in a few taps and track it until it arrives.') }}</p>
                        <x-ui.button color="surface" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('Browse Products') }}</x-ui.button>
                    </div>
                    <x-ui.card class="mx-auto w-full max-w-md p-8">
                        <x-brand.app-logo-icon class="w-full" />
                    </x-ui.card>
                </div>
            @endif

            <div class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                @foreach ([
                    ['local_shipping', __('Home delivery'), __('Delivered to your address')],
                    ['verified', __('Quality products'), __('Checked before they ship')],
                    ['track_changes', __('Order tracking'), __('Follow every step')],
                    ['support_agent', __('Friendly support'), __('Here when you need us')],
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
        <x-frontend.layout.section id="categories" :title="__('Browse by category')" muted class="[&>div]:pt-12 [&>div]:pb-16">
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
        <x-frontend.layout.section muted class="[&>div]:pt-0 [&>div]:pb-14">
            <x-frontend.content.offer-banner :offer="$offer" />
        </x-frontend.layout.section>
    @endif

    @if ($featuredProducts->isNotEmpty())
        <x-frontend.layout.section id="featured" :title="__('Featured products')" :description="__('Hand-picked by our team')" class="[&>div]:py-14">
            <x-slot:actions>
                <x-ui.button variant="ghost" size="sm" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('View All') }}</x-ui.button>
            </x-slot:actions>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($featuredProducts as $product)
                    <x-frontend.cards.product-tile :product="$product" />
                @endforeach
            </div>
        </x-frontend.layout.section>
    @endif

    @if ($newArrivals->isNotEmpty())
        <x-frontend.layout.section id="new" :title="__('New arrivals')" :description="__(':count products and growing', ['count' => number_format($productsCount)])" muted class="[&>div]:py-14">
            <x-slot:actions>
                <x-ui.button variant="ghost" size="sm" icon-trailing="arrow-right" :href="route('shop')" wire:navigate>{{ __('View All') }}</x-ui.button>
            </x-slot:actions>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($newArrivals as $product)
                    <x-frontend.cards.product-tile :product="$product" />
                @endforeach
            </div>
        </x-frontend.layout.section>
    @endif

    <x-frontend.layout.section id="how" :title="__('How to buy')" :description="__('Three simple steps.')" class="[&>div]:py-16">
        <div class="grid gap-5 md:grid-cols-3">
            <x-frontend.cards.feature-card icon="shopping_cart" :title="__('1. Place your order')" :description="__('Pick your products, add them to the cart and check out.')" />
            <x-frontend.cards.feature-card icon="fact_check" :title="__('2. We confirm it')" :description="__('We check the order and prepare it for delivery.')" />
            <x-frontend.cards.feature-card icon="local_shipping" :title="__('3. Get it delivered')" :description="__('Your order arrives at your door. Track it any time.')" />
        </div>
    </x-frontend.layout.section>

    <section class="mx-auto max-w-main px-4 pb-20 sm:px-6">
        <x-ui.banner tone="brand" size="none" class="relative overflow-hidden rounded-2xl px-6 py-12 text-center sm:px-12">
            <h2 class="relative text-3xl font-bold text-zinc-900 dark:text-white">{{ __('Shop smarter every day') }}</h2>
            <p class="relative mx-auto mt-3 max-w-xl text-zinc-600 dark:text-zinc-300">{{ __('Create a free account to track your orders and check out faster.') }}</p>
            <div class="relative mt-6 flex flex-wrap justify-center gap-3">
                @guest
                    <x-ui.button variant="primary" :href="route('user.register')" wire:navigate>{{ __('Create Free Account') }}</x-ui.button>
                @endguest
                <x-ui.button icon="building-storefront" :href="route('shop')" wire:navigate>{{ __('Shop') }}</x-ui.button>
            </div>
        </x-ui.banner>
    </section>
</x-layouts.frontend>
