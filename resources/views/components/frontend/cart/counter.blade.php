<div>
    <x-frontend.drawers.trigger name="cart">
        <button type="button" class="relative inline-flex size-8 items-center sm:size-9 justify-center rounded-lg text-bar-foreground transition-colors hover:bg-bar-hover hover:text-bar-active-foreground" aria-label="{{ __('Cart') }}">
            <span class="material-symbols-outlined text-xl">shopping_cart</span>
            <x-ui.counter :count="$this->count" tone="accent" />
        </button>
    </x-frontend.drawers.trigger>
</div>
