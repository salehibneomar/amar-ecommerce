<div>
    @if ($this->items->isEmpty())
        <x-frontend.content.empty-state icon="shopping_cart" :title="__('Your cart is empty')">
            <x-ui.button variant="primary" icon="building-storefront" :href="route('shop')" wire:navigate>{{ __('Shop') }}</x-ui.button>
        </x-frontend.content.empty-state>
    @else
        <form wire:submit="placeOrder" class="grid gap-8 lg:grid-cols-[1fr_22rem]">
            <div class="flex flex-col gap-6">
                <flux:error name="cart" />

                @guest
                    <x-frontend.content.notice icon="person">
                        {{ __('Have an account? Log in to check out faster with your saved details.') }}

                        <x-slot:action>
                            <x-ui.button size="sm" variant="ghost" :href="route('user.login')" wire:navigate>{{ __('Log in') }}</x-ui.button>
                        </x-slot:action>
                    </x-frontend.content.notice>
                @endguest

                <x-ui.card as="section" class="space-y-4 p-5">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Your details') }}</h2>
                    @guest
                        <div class="grid gap-4 sm:grid-cols-2">
                            <flux:input wire:model="name" :label="__('Name')" required autocomplete="name" />
                            <flux:input wire:model="email" type="email" :label="__('Email Address')" required autocomplete="email" />
                        </div>
                    @endguest
                    <flux:input wire:model="phone" type="tel" :label="__('Mobile Number')" placeholder="01XXXXXXXXX" required autocomplete="tel" />
                    <flux:textarea wire:model="shippingAddress" :label="__('Delivery Address')" :placeholder="__('House, road, area, district')" rows="3" required autocomplete="shipping street-address" />
                    <flux:checkbox wire:model="billingSameAsShipping" :label="__('Billing Address Same as Delivery')" />
                    <div x-show="! $wire.billingSameAsShipping" x-cloak>
                        <flux:textarea wire:model="billingAddress" :label="__('Billing Address')" :placeholder="__('House, road, area, district')" rows="3" autocomplete="billing street-address" />
                    </div>
                    <flux:textarea wire:model="notes" :label="__('Note (Optional)')" rows="2" />
                    @guest
                        <flux:checkbox wire:model="createAccount" :label="__('Create an Account With These Details')" />
                        <div x-show="$wire.createAccount" x-cloak class="grid gap-4 sm:grid-cols-2">
                            <flux:input wire:model="password" type="password" :label="__('Password')" autocomplete="new-password" viewable />
                            <flux:input wire:model="password_confirmation" type="password" :label="__('Confirm Password')" autocomplete="new-password" viewable />
                        </div>
                    @endguest
                </x-ui.card>

                @if (count($this->gateways))
                    <x-ui.card as="section" class="space-y-4 p-5">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Payment') }}</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($this->gateways as $gateway)
                                <label wire:key="gateway-{{ $gateway->value }}" class="flex cursor-pointer items-start gap-3 rounded-lg border border-line p-4 transition has-checked:border-brand has-checked:bg-brand/5 has-focus-visible:ring-2 has-focus-visible:ring-brand/40">
                                    <input type="radio" wire:model="paymentGateway" value="{{ $gateway->value }}" class="sr-only" />
                                    <span class="material-symbols-outlined text-brand-text">{{ $gateway->icon() }}</span>
                                    <span class="grid gap-0.5">
                                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $gateway->label() }}</span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $gateway->description() }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <flux:error name="paymentGateway" />
                        <div x-show="$wire.paymentGateway === @js(\App\Enums\Payment\PaymentGatewayEnum::Manual->value)" x-cloak>
                            <x-ui.alert icon="chat-bubble-left-right" color="green" :heading="__('How payment works')" :text="__('After you place the order, contact us on WhatsApp, Messenger or email with your order ID. We confirm your bKash or Nagad payment and process your order.')" />
                        </div>
                    </x-ui.card>
                @endif
            </div>

            <x-ui.card as="aside" class="h-fit p-5 lg:sticky lg:top-24">
                <h2 class="mb-4 font-semibold text-zinc-900 dark:text-white">{{ __('Your order') }}</h2>
                <x-frontend.cart.lines :items="$this->items" />
                <x-frontend.cart.summary :summary="$this->summary" />
                <x-ui.button type="submit" variant="primary" class="mt-5 w-full" icon="check">
                    <span x-show="$wire.paymentGateway !== @js(\App\Enums\Payment\PaymentGatewayEnum::Stripe->value)">{{ __('Place Order') }}</span>
                    <span x-show="$wire.paymentGateway === @js(\App\Enums\Payment\PaymentGatewayEnum::Stripe->value)" x-cloak>{{ __('Continue to Payment') }}</span>
                </x-ui.button>
                <p class="mt-3 text-center text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('By placing the order you agree to our') }}
                    <a href="{{ route('refund-policy') }}" class="underline" wire:navigate>{{ __('order policy') }}</a>.
                </p>
            </x-ui.card>
        </form>
    @endif
</div>
