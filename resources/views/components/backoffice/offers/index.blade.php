<div class="flex flex-col gap-6">
    <x-backoffice.layout.main-content-container :title="__('Offers')" :breadcrumbs="[['label' => __('Settings'), 'href' => route('settings.index')], ['label' => __('Offers')]]" :subtitle="$this->globalOnly ? __('Discounts for the whole shop, applied at checkout. One offer can be live at a time.') : __('Discounts for the whole shop or for chosen categories, applied at checkout.')">
        @if ($this->globalOnly)
            <x-slot:badge>
                <x-ui.badge color="zinc">{{ __('All offers are global') }}</x-ui.badge>
            </x-slot:badge>
        @endif

        <x-backoffice.management.table :sortable="$this->tableSortableColumns" :columns="[
            'id' => __('ID'),
            'name' => __('Name'),
            ...($this->globalOnly ? [] : ['type' => __('Type')]),
            'discount_percentage' => __('Discount'),
            'is_active' => __('Status'),
            'ends_at' => __('Schedule'),
            'actions' => __('Actions'),
        ]" :sort-by="$sortBy" :sort-direction="$sortDirection">
            <x-slot:toolbar>
                <div class="flex items-center gap-2">
                    <x-backoffice.management.per-page :placeholder="__('Select')" />
                </div>
                <div class="flex items-end gap-2">
                    <x-backoffice.management.search model="search" :placeholder="__('Search offers...')" :label="__('Search Offers')" />
                    <x-ui.button variant="primary" wire:click="createOffer">{{ __('Add New') }}</x-ui.button>
                </div>
            </x-slot:toolbar>

            <x-slot:rows>
                @forelse ($this->offers as $offer)
                    <flux:table.row wire:key="offer-{{ $offer->id }}">
                        <flux:table.cell>{{ $offer->id }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="max-w-xs truncate font-medium">{{ $offer->name }}</div>
                            <div class="max-w-xs truncate text-xs text-zinc-500">{{ $offer->headline }}</div>
                        </flux:table.cell>
                        @unless ($this->globalOnly)
                            <flux:table.cell>
                                <x-ui.badge :color="$offer->type->color()">{{ $offer->type->label() }}
                                </x-ui.badge>
                            </flux:table.cell>
                        @endunless
                        <flux:table.cell><span class="font-semibold">{{ $offer->discount_percentage }}%</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <x-ui.badge :color="$offer->isCurrentlyActive() ? 'green' : 'zinc'">
                                {{ $offer->isCurrentlyActive() ? __('Live') : __('Not live') }}
                            </x-ui.badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-zinc-500">
                            {{ $offer->starts_at?->format('d/m/Y') ?? appEmptyValue() }} →
                            {{ $offer->ends_at?->format('d/m/Y') ?? '∞' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <x-backoffice.management.actions>
                                <x-backoffice.management.action icon="eye" :label="__('View')" variant="filled" wire:click="viewOffer({{ $offer->id }})" />
                                <x-backoffice.management.action icon="pencil-square" :label="__('Edit')" variant="filled" color="sky" wire:click="editOffer({{ $offer->id }})" />
                                <x-backoffice.management.action icon="trash" :label="__('Delete')" variant="danger" wire:click="confirmDeleteOffer({{ $offer->id }})" />
                            </x-backoffice.management.actions>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell :colspan="$this->globalOnly ? 6 : 7">{{ __('No offers found.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-slot:rows>

            <x-slot:pagination><x-backoffice.management.pagination :paginator="$this->offers" /></x-slot:pagination>
        </x-backoffice.management.table>

        <x-backoffice.management.form-modal wire:model="showForm" size="lg" :title="$editingOfferId ? __('Edit Offer') : __('Add Offer')" submit="saveOffer">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_8rem]">
                <flux:input wire:model="name" :label="__('Name')" required />
                <flux:input wire:model="discountPercentage" type="number" min="1" max="100"
                    :label="__('Discount (%)')" required />
            </div>
            <flux:input wire:model="headline" maxlength="120" :label="__('Headline')" required />
            @unless ($this->globalOnly)
                <flux:radio.group wire:model.live="type" :label="__('Applies to')" variant="segmented">
                    @foreach ($this->typeOptions as $value => $label)
                        <flux:radio :value="$value" :label="$label" />
                    @endforeach
                </flux:radio.group>
            @endunless
            @if ($this->isSpecific)
                <flux:field>
                    <flux:label>{{ __('Categories') }}</flux:label>
                    <flux:description>{{ __('The System only allows to add root categories.') }}</flux:description>
                    <div class="relative">
                        <x-ui.search-input model="categorySearch" :placeholder="__('Search categories to add...')" />
                        @if ($this->categoryResults)
                            <div
                                class="absolute inset-x-0 top-full z-10 mt-1 max-h-56 overflow-y-auto rounded-lg border border-line bg-surface p-1 shadow-lg">
                                @foreach ($this->categoryResults as $id => $label)
                                    <button type="button" wire:key="category-result-{{ $id }}"
                                        wire:click="addCategory({{ $id }})"
                                        class="block w-full cursor-pointer rounded-md px-3 py-2 text-start text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700">{{ $label }}</button>
                                @endforeach
                            </div>
                        @elseif (filled($categorySearch))
                            <flux:text class="mt-1 text-xs">{{ __('No matching categories.') }}</flux:text>
                        @endif
                    </div>
                    @if ($this->selectedCategories)
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            @foreach ($this->selectedCategories as $id => $label)
                                <x-ui.badge wire:key="offer-category-{{ $id }}"
                                    color="sky">
                                    {{ $label }}
                                    <flux:badge.close wire:click="removeCategory({{ $id }})"
                                        :aria-label="__('Remove :name', ['name' => $label])" />
                                </x-ui.badge>
                            @endforeach
                        </div>
                    @endif
                    <flux:error name="categoryIds" />
                </flux:field>
            @endif
            <flux:textarea wire:model="description" :label="__('Description')" rows="2" />
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 [&_input]:min-w-0">
                <flux:input wire:model.live="startsAt" type="date" :label="__('Starts')" />
                <flux:input wire:model.live="endsAt" type="date" :label="__('Ends')" />
            </div>
            @if ($this->liveGlobalOverlap)
                <x-ui.alert variant="warning" icon="exclamation-triangle" :heading="__(':name is live from :from to :to. Turn it off first to make this offer active. Until then it saves as inactive.', [
                    'name' => $this->liveGlobalOverlap->name,
                    'from' => $this->liveGlobalOverlap->starts_at?->format('d/m/Y') ?? appEmptyValue(),
                    'to' => $this->liveGlobalOverlap->ends_at?->format('d/m/Y') ?? '∞',
                ])" />
            @endif
            <x-backoffice.form.toggle-field wire:model="isActive" :label="__('Active')" :disabled="(bool) $this->liveGlobalOverlap" />
            <flux:error name="is_active" />
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showDeleteConfirmation" size="md" :title="__('Delete offer?')">
            <flux:text>{{ __('This offer will be deleted permanently. Past orders keep their discount.') }}</flux:text>
            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <x-ui.button type="button">{{ __('Close') }}</x-ui.button>
                    </flux:modal.close>
                    <x-ui.button variant="danger" wire:click="deleteOffer">{{ __('Delete') }}</x-ui.button>
                </div>
            </x-slot:footer>
        </x-backoffice.management.form-modal>

        <x-backoffice.management.details-modal wire:model="showDetails" :title="__('Offer Details')" :items="$details" />
    </x-backoffice.layout.main-content-container>
</div>
