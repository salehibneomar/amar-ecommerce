<div class="grid gap-8 lg:grid-cols-[15rem_1fr]">
    <x-frontend.drawers.filter-drawer :categories="$this->categories['items']" :has-more="$this->categories['has_more']" :has-filters="$this->hasFilters" />

    <div class="flex min-w-0 flex-col gap-6">
        <div class="flex flex-wrap items-end gap-3">
            <div class="shrink-0 lg:hidden">
                <flux:field>
                    <flux:label>{{ __('Filters') }}</flux:label>
                    <x-frontend.drawers.trigger name="filters">
                        <x-ui.button color="field" square icon="funnel" icon:variant="outline" class="relative" :aria-label="__('Filters')">
                            @if ($this->hasFilters)
                                <span class="absolute -end-1 -top-1 size-2.5 rounded-full bg-brand-600"></span>
                            @endif
                        </x-ui.button>
                    </x-frontend.drawers.trigger>
                </flux:field>
            </div>
            <flux:select wire:model.live="sort" :label="__('Sort by')" class="min-w-0 flex-1 lg:w-52 lg:flex-none">
                @foreach ($this->sortOptions as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            @if ($this->hasFilters)
                <x-ui.button icon="x-mark" class="shrink-0" wire:click="clearFilters">{{ __('Clear') }}</x-ui.button>
            @endif
            <div class="w-full max-lg:order-last lg:ms-auto lg:w-72">
                <x-ui.search-input model="search" :label="__('Search')" :placeholder="__('Title or author...')" clearable />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3" wire:loading.class="opacity-60">
            @forelse ($this->products as $product)
                <x-frontend.cards.product-tile wire:key="product-{{ $product->id }}" :product="$product" :owned="in_array($product->id, $this->ownedIds, true)" />
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                    <span class="material-symbols-outlined text-4xl! text-zinc-400">menu_book</span>
                    <p class="mt-2 text-zinc-600 dark:text-zinc-300">{{ __('No books match your search.') }}</p>
                </div>
            @endforelse
        </div>

        <x-backoffice.management.pagination :paginator="$this->products" :summary="__('Showing :from to :to of :total books')" />
    </div>
</div>
