<div>
    @if ($this->items->isEmpty())
        <div class="flex flex-col items-center gap-4 rounded-xl border border-dashed border-zinc-300 px-6 py-16 text-center dark:border-zinc-700">
            <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Your cart is empty') }}</p>
            <x-ui.button variant="primary" icon="building-storefront" :href="route('shop')" wire:navigate>{{ __('Shop') }}</x-ui.button>
        </div>
    @else
        <form wire:submit="placeOrder" class="grid gap-8 lg:grid-cols-[1fr_22rem]">
            <div class="flex flex-col gap-6">
                <flux:error name="cart" />

                @guest
                    <x-frontend.content.notice icon="lock">
                        {{ __('Create a free account before placing your order. It keeps your digital books available in My library and lets you track your order.') }}

                        <x-slot:action>
                            <div class="flex gap-2">
                                <x-ui.button size="sm" variant="primary" :href="route('user.register')" wire:navigate>{{ __('Create Account') }}</x-ui.button>
                                <x-ui.button size="sm" variant="ghost" :href="route('user.login')" wire:navigate>{{ __('Log in') }}</x-ui.button>
                            </div>
                        </x-slot:action>
                    </x-frontend.content.notice>
                @endguest

                @auth
                <x-ui.card as="section" class="space-y-4 p-5">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Your details') }}</h2>
                    <flux:input wire:model="phone" type="tel" :label="__('Mobile Number')" placeholder="01XXXXXXXXX" required autocomplete="tel" />
                    @if ($this->summary['requiresShipping'])
                        <flux:textarea wire:model="shippingAddress" :label="__('Delivery Address')" :placeholder="__('House, road, area, district')" rows="3" required />
                    @endif
                    <flux:textarea wire:model="notes" :label="__('Note (Optional)')" rows="2" />
                </x-ui.card>
                @endauth

                <x-ui.alert icon="chat-bubble-left-right" color="green" :heading="__('How payment works')" :text="__('After you place the order, contact us on WhatsApp, Messenger or email with your order ID. We confirm your bKash or Nagad payment and unlock your books in My library.')" />
            </div>

            <x-ui.card as="aside" class="h-fit p-5 lg:sticky lg:top-24">
                <h2 class="mb-4 font-semibold text-zinc-900 dark:text-white">{{ __('Your order') }}</h2>
                <ul class="mb-4 space-y-2 border-b border-line pb-4 text-sm">
                    @foreach ($this->items as $item)
                        <li wire:key="checkout-item-{{ $item['product']->id }}" class="flex justify-between gap-3">
                            <span class="min-w-0 truncate text-zinc-700 dark:text-zinc-300">{{ $item['product']->name }} <span class="text-zinc-400">× {{ $item['quantity'] }}</span></span>
                            <x-ui.price :amount="$item['line_total']" :original="$item['original_total']" size="sm" class="shrink-0" />
                        </li>
                    @endforeach
                </ul>
                <x-frontend.cart.summary :summary="$this->summary" />
                @auth
                    <x-ui.button type="submit" variant="primary" class="mt-5 w-full" icon="check">{{ __('Place Order') }}</x-ui.button>
                @else
                    <x-ui.button variant="primary" class="mt-5 w-full" icon="user-plus" :href="route('user.register')" wire:navigate>{{ __('Create an Account to Place Order') }}</x-ui.button>
                @endauth
                <p class="mt-3 text-center text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('By placing the order you agree to our') }}
                    <a href="{{ route('refund-policy') }}" class="underline" wire:navigate>{{ __('order policy') }}</a>.
                </p>
            </x-ui.card>
        </form>
    @endif
</div>
