<x-ui.popover :label="__('Search books')">
    <x-slot:trigger>
        <flux:button variant="subtle" square class="icon-bold max-sm:size-8! text-white! hover:bg-white/15!" x-bind:aria-expanded="open" :aria-label="__('Search books')" data-test="search-button">
            <span class="material-symbols-outlined text-xl">search</span>
        </flux:button>
    </x-slot:trigger>

    <div class="space-y-3 p-4">
        <x-ui.search-input model="query" :placeholder="__('Search books by title...')" :aria-label="__('Search books by title')" clearable />

        @if ($this->isSearching)
            <div class="grid max-h-[60vh] gap-2 overflow-y-auto" wire:loading.class="opacity-60" data-test="search-results">
                @forelse ($this->visibleResults as $product)
                    <x-frontend.cards.product-list-item wire:key="search-result-{{ $product->id }}" :product="$product" />
                @empty
                    <p class="py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('No books match ":query".', ['query' => trim($query)]) }}</p>
                @endforelse

                @if ($this->hasMore)
                    <flux:button variant="ghost" icon-trailing="arrow-right" class="w-full" :href="route('shop', ['q' => trim($query)])" wire:navigate data-test="search-see-more">{{ __('See more') }}</flux:button>
                @endif
            </div>
        @endif
    </div>
</x-ui.popover>
