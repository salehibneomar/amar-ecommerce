<div class="flex flex-col gap-6">
    <x-backoffice.layout.main-content-container :title="__('Manage Banners')" :subtitle="__('Slides shown at the top of the home page.')">
        <x-backoffice.management.table :sortable="$this->tableSortableColumns" :columns="[
            'id' => __('ID'),
            'image' => __('Image'),
            'title' => __('Title'),
            'sort_order' => __('Order'),
            'status' => __('Status'),
            'ends_at' => __('Schedule'),
            'actions' => __('Actions'),
        ]" :sort-by="$sortBy" :sort-direction="$sortDirection">
            <x-slot:toolbar>
                <div class="flex items-center gap-2">
                    <x-backoffice.management.per-page :placeholder="__('Select')" />
                    <x-backoffice.management.filter model="status" :label="__('Filter by status')" :options="$this->statusOptions" />
                </div>
                <div class="flex items-end gap-2">
                    <x-backoffice.management.search model="search" :placeholder="__('Search banners...')" :label="__('Search banners')" class="max-w-xs" />
                    <flux:button variant="primary" wire:click="createBanner">{{ __('Add New') }}</flux:button>
                </div>
            </x-slot:toolbar>

            <x-slot:rows>
                @forelse ($this->banners as $banner)
                    <flux:table.row wire:key="banner-{{ $banner->id }}">
                        <flux:table.cell>{{ $banner->id }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($banner->image_thumbnail_url)
                                <img src="{{ $banner->image_thumbnail_url }}" alt="" class="h-10 w-28 rounded object-cover ring-1 ring-zinc-950/10" loading="lazy" />
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="max-w-xs truncate font-medium">{{ $banner->title }}</div>
                            <div class="max-w-xs truncate text-xs text-zinc-500">{{ $banner->subtitle }}</div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $banner->sort_order }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$banner->status->color()">
                                {{ $banner->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-zinc-500">
                            {{ $banner->starts_at?->format('d/m/Y') ?? '-' }} → {{ $banner->ends_at?->format('d/m/Y') ?? '∞' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button size="xs" variant="filled" wire:click="viewBanner({{ $banner->id }})">{{ __('View') }}</flux:button>
                                <flux:button size="xs" variant="filled" color="sky" wire:click="editBanner({{ $banner->id }})">{{ __('Edit') }}</flux:button>
                                <flux:button size="xs" variant="danger" wire:click="confirmDeleteBanner({{ $banner->id }})">{{ __('Delete') }}</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">{{ __('No banners found.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-slot:rows>

            <x-slot:pagination><x-backoffice.management.pagination :paginator="$this->banners" /></x-slot:pagination>
        </x-backoffice.management.table>

        <x-backoffice.management.form-modal wire:model="showForm" size="2xl" :title="$editingBannerId ? __('Edit Banner') : __('Add Banner')" submit="saveBanner">
            <x-media.upload :label="__('Banner image (wide, 1600×600 recommended)')" :field="$this->imageField" upload="imageUpload" :selected="$imageUpload" />
            <flux:input wire:model="title" :label="__('Title')" required />
            <flux:input wire:model="subtitle" :label="__('Subtitle')" />
            <x-backoffice.form.toggle-field wire:model="showText" :label="__('Show title, subtitle and button on the slide (turn off when the image already has everything)')" />
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="link" :label="__('Link')" placeholder="/shop" />
                <flux:input wire:model="sortOrder" type="number" min="1" :label="__('Display order')" />
                <flux:input wire:model="startsAt" type="datetime-local" :label="__('Show from')" />
                <flux:input wire:model="endsAt" type="datetime-local" :label="__('Show until')" />
            </div>
            <flux:select wire:model="bannerStatus" :label="__('Status')">
                @foreach ($this->statusOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showDeleteConfirmation" size="md" :title="__('Delete banner?')">
            <flux:text>{{ __('This banner and its image will be deleted permanently.') }}</flux:text>
            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="deleteBanner">{{ __('Delete') }}</flux:button>
                </div>
            </x-slot:footer>
        </x-backoffice.management.form-modal>

        <x-backoffice.management.details-modal wire:model="showDetails" :title="__('Banner Details')" :items="$details" />
    </x-backoffice.layout.main-content-container>
</div>
