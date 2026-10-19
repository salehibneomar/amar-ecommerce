<div>
    <x-frontend.drawers.drawer name="cart" :label="__('Your Cart')" x-on:click="$event.target.closest('a') && (open = false)">
        <x-slot:header>
            <div class="flex items-center gap-3">
                <x-ui.icon-badge size="sm">
                    <span class="material-symbols-outlined">shopping_cart</span>
                </x-ui.icon-badge>
                <flux:heading size="lg">{{ __('Your cart') }}</flux:heading>
            </div>
            <x-ui.button variant="ghost" size="sm" icon="x-mark" x-on:click="open = false" :aria-label="__('Close cart')" />
        </x-slot:header>

        @if ($this->items->isEmpty())
            <div class="flex flex-1 flex-col items-center justify-center gap-4 py-16 text-center">
                <x-ui.icon-badge size="2xl" circle>
                    <span class="material-symbols-outlined text-3xl!">shopping_cart</span>
                </x-ui.icon-badge>
                <div>
                    <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Your cart is empty') }}</p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Find a book you like and add it here.') }}</p>
                </div>
                <x-ui.button variant="primary" icon="building-storefront" :href="route('shop')" wire:navigate>{{ __('Shop') }}</x-ui.button>
            </div>
        @else
            <ul class="flex-1 space-y-3" wire:loading.class="opacity-60">
                @foreach ($this->items as $item)
                    <x-ui.card as="li" wire:key="cart-item-{{ $item['product']->id }}" class="flex gap-3 p-2.5">
                        <a href="{{ route('shop.product', $item['product']->slug) }}" wire:navigate class="h-20 w-15 shrink-0 overflow-hidden rounded-md bg-media">
                            @if ($item['product']->cover_thumbnail_url)
                                <img src="{{ $item['product']->cover_thumbnail_url }}" alt="" class="size-full object-cover" />
                            @else
                                <span class="flex size-full items-center justify-center text-brand-text"><span class="material-symbols-outlined">auto_stories</span></span>
                            @endif
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <a href="{{ route('shop.product', $item['product']->slug) }}" wire:navigate class="line-clamp-2 text-sm font-medium text-zinc-900 hover:text-brand-text dark:text-white">{{ $item['product']->name }}</a>
                            @if ($item['product']->author)
                                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $item['product']->author }}</p>
                            @endif
                            <div class="mt-auto flex items-center justify-between gap-2 pt-1">
                                @if ($item['product']->is_digital)
                                    <span></span>
                                @else
                                    <div class="flex items-center overflow-hidden rounded-md border border-line bg-subtle">
                                        <button type="button" class="px-2 py-0.5 text-zinc-600 transition-colors hover:bg-menu-hover hover:text-bar-active-foreground dark:text-zinc-300" wire:click="updateQuantity({{ $item['product']->id }}, {{ $item['quantity'] - 1 }})" aria-label="{{ __('Decrease') }}">−</button>
                                        <span class="min-w-7 text-center text-sm font-medium">{{ $item['quantity'] }}</span>
                                        <button type="button" class="px-2 py-0.5 text-zinc-600 transition-colors hover:bg-menu-hover hover:text-bar-active-foreground dark:text-zinc-300" wire:click="updateQuantity({{ $item['product']->id }}, {{ $item['quantity'] + 1 }})" aria-label="{{ __('Increase') }}">+</button>
                                    </div>
                                @endif
                                <div class="flex items-center gap-2">
                                    <x-ui.price :amount="$item['line_total']" :original="$item['original_total']" size="sm" />
                                    <x-ui.button size="xs" variant="danger" square icon="trash" wire:click="remove({{ $item['product']->id }})" :aria-label="__('Remove')" />
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </ul>
        @endif

        @if ($this->items->isNotEmpty())
            <x-slot:footer>
                <div class="space-y-3">
                    <x-frontend.cart.summary :summary="$this->summary" class="rounded-lg border border-line bg-subtle p-3" />
                    <x-ui.button variant="primary" class="w-full" icon-trailing="arrow-right" :href="route('checkout')" wire:navigate>{{ __('Checkout') }}</x-ui.button>
                    <x-ui.button variant="ghost" class="w-full" x-on:click="open = false">{{ __('Continue Shopping') }}</x-ui.button>
                </div>
            </x-slot:footer>
        @endif
    </x-frontend.drawers.drawer>
</div>
