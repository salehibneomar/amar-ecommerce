<x-layouts::frontend :title="__('Payment')">
    <x-frontend.layout.page :title="__('Complete your payment')" :subtitle="isset($order) ? __('Order ID: :order', ['order' => $order->public_order_id]) : __('Your order is placed when the payment goes through.')">
        <div class="grid gap-8 lg:grid-cols-[1fr_20rem]">
            <x-ui.card as="section" class="h-fit p-5">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Payment Details') }}</h2>
                    <div class="flex items-center gap-2">
                        @if ($page['isTestMode'] ?? false)
                            <x-ui.badge color="amber">{{ __('Test Mode') }}</x-ui.badge>
                        @endif
                        <span class="inline-flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="material-symbols-outlined text-base">lock</span>
                            {{ __('Secured by Stripe') }}
                        </span>
                    </div>
                </div>

                @if ($page['unavailable'] ?? false)
                    <x-ui.alert icon="exclamation-triangle" variant="danger" :heading="__('Card payment is not available right now')" :text="__('Please try again in a moment. Your order is saved.')">
                        <x-slot:actions>
                            <x-ui.button size="sm" icon="arrow-path" :href="request()->fullUrl()">{{ __('Try Again') }}</x-ui.button>
                        </x-slot:actions>
                    </x-ui.alert>
                    <x-ui.button variant="ghost" icon="arrow-left" :href="$orderUrl" class="mt-4 w-full">{{ __('Pay Later and View Order') }}</x-ui.button>
                @else
                    <form x-data="stripePayment(@js($page))" x-on:submit.prevent="pay" data-load-error="{{ __('The payment form could not load. Please refresh the page.') }}" data-place-error="{{ __('We could not place your order. Please try again.') }}" class="space-y-5">
                        <div x-show="! ready" class="space-y-3" aria-hidden="true">
                            <div class="h-11 animate-pulse rounded-md bg-media"></div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="h-11 animate-pulse rounded-md bg-media"></div>
                                <div class="h-11 animate-pulse rounded-md bg-media"></div>
                            </div>
                        </div>
                        @csrf
                        <div x-ref="element"></div>
                        <p x-show="error" x-text="error" x-cloak role="alert" class="text-sm text-red-600 dark:text-red-400"></p>
                        <x-ui.button type="submit" variant="primary" class="w-full" icon="lock-closed" x-bind:disabled="! ready || processing">
                            <span x-show="! processing">{{ __('Pay :amount', ['amount' => formatMoney($order->total ?? $summary['total'])]) }}</span>
                            <span x-show="processing" x-cloak>{{ __('Processing') }}</span>
                        </x-ui.button>
                        @isset($order)
                            <x-ui.button variant="ghost" icon="arrow-left" :href="$orderUrl" class="w-full">{{ __('Pay Later and View Order') }}</x-ui.button>
                        @else
                            <x-ui.button variant="ghost" icon="arrow-left" :href="route('checkout')" class="w-full" x-show="! orderUrl">{{ __('Back to Checkout') }}</x-ui.button>
                            <x-ui.button variant="ghost" icon="arrow-right" href="#" class="w-full" x-show="orderUrl" x-bind:href="orderUrl" x-cloak>{{ __('Pay Later and View Order') }}</x-ui.button>
                        @endisset
                    </form>
                @endif
            </x-ui.card>

            @isset($order)
                <x-frontend.orders.items :order="$order" class="h-fit lg:sticky lg:top-24" />
            @else
                <x-ui.card as="aside" class="h-fit p-5 lg:sticky lg:top-24">
                    <h2 class="mb-4 font-semibold text-zinc-900 dark:text-white">{{ __('Your order') }}</h2>
                    <x-frontend.cart.lines :items="$items" />
                    <x-frontend.cart.summary :summary="$summary" />
                </x-ui.card>
            @endisset
        </div>
    </x-frontend.layout.page>
</x-layouts::frontend>
