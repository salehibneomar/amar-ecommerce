<div class="grid gap-8 lg:grid-cols-[15rem_1fr]">
    <x-frontend.drawers.filter-drawer :categories="$this->categories['items']" :has-more="$this->categories['has_more']" :has-filters="$this->hasFilters" />

    <div class="flex min-w-0 flex-col gap-6">
        <div class="flex flex-wrap items-end gap-3">
            <div class="shrink-0 lg:hidden">
                <flux:field>
                    <flux:label>{{ __('Filters') }}</flux:label>
                    <x-ui.drawer-trigger name="filters">
                        <x-ui.button color="field" square icon="funnel" icon:variant="outline" class="relative" :aria-label="__('Filters')">
                            @if ($this->hasFilters)
                                <span class="absolute -end-1 -top-1 size-2.5 rounded-full bg-brand-600"></span>
                            @endif
                        </x-ui.button>
                    </x-ui.drawer-trigger>
                </flux:field>
            </div>
            <x-ui.select model="sort" :label="__('Sort by')" :options="$this->sortOptions" class="min-w-0 flex-1 lg:w-52 lg:flex-none" live />
            @if ($this->hasFilters)
                <x-ui.button icon="x-mark" class="shrink-0" wire:click="clearFilters">{{ __('Clear') }}</x-ui.button>
            @endif
            <div class="w-full max-lg:order-last lg:ms-auto lg:w-72">
                <x-ui.search-input model="search" :label="__('Search')" :placeholder="__('Product name...')" clearable />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-3" wire:loading.class="opacity-60">
            @forelse ($this->products as $product)
                <x-frontend.cards.product-tile wire:key="product-{{ $product->id }}" :product="$product" />
            @empty
                <x-frontend.content.empty-state icon="inventory_2" :title="__('No products match your search.')" class="col-span-full" />
            @endforelse
        </div>

        <x-backoffice.management.pagination :paginator="$this->products" :summary="__('Showing :from to :to of :total products')" />
    </div>

    <x-analytics.event :event="$this->analyticsEvent" />
</div>
