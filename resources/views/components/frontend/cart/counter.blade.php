<div>
    <x-frontend.drawers.trigger name="cart">
        <button type="button" class="relative inline-flex size-8 items-center sm:size-9 justify-center rounded-lg text-white transition-colors hover:bg-white/15 hover:text-white" aria-label="{{ __('Cart') }}">
            <flux:icon.shopping-cart variant="solid" class="size-5" />
            @if ($this->count > 0)
                <span class="absolute -end-0.5 -top-0.5 flex min-w-4.5 items-center justify-center rounded-full bg-gold-400 px-1 text-[10px] font-bold leading-4.5 text-zinc-900">{{ $this->count > 99 ? '99+' : $this->count }}</span>
            @endif
        </button>
    </x-frontend.drawers.trigger>
</div>
