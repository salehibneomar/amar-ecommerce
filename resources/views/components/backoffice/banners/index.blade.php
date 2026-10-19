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
                    <x-backoffice.management.filter model="status" :label="__('Filter by Status')" :options="$this->statusOptions" />
                </div>
                <div class="flex items-end gap-2">
                    <x-backoffice.management.search model="search" :placeholder="__('Search banners...')" :label="__('Search Banners')" class="max-w-xs" />
                    <x-ui.button variant="primary" wire:click="createBanner">{{ __('Add New') }}</x-ui.button>
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
                                <x-ui.empty-value />
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="max-w-xs truncate font-medium">{{ $banner->title }}</div>
                            <div class="max-w-xs truncate text-xs text-zinc-500">{{ $banner->subtitle }}</div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $banner->sort_order }}</flux:table.cell>
                        <flux:table.cell>
                            <x-ui.badge :color="$banner->status->color()">
                                {{ $banner->status->label() }}
                            </x-ui.badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-xs text-zinc-500">
                            {{ $banner->starts_at?->format('d/m/Y') ?? appEmptyValue() }} → {{ $banner->ends_at?->format('d/m/Y') ?? '∞' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <x-backoffice.management.actions>
                                <x-backoffice.management.action icon="eye" :label="__('View')" variant="filled" wire:click="viewBanner({{ $banner->id }})" />
                                <x-backoffice.management.action icon="pencil-square" :label="__('Edit')" variant="filled" color="sky" wire:click="editBanner({{ $banner->id }})" />
                                @if ($banner->image_thumbnail_url)
                                    <x-backoffice.management.action icon="photo" :label="__('Media')" variant="filled" color="green" wire:click="viewMedia({{ $banner->id }})" />
                                @endif
                                <x-backoffice.management.action icon="arrow-up-tray" :label="__('Upload')" variant="filled" color="violet" wire:click="uploadMedia({{ $banner->id }})" />
                                <x-backoffice.management.action icon="trash" :label="__('Delete')" variant="danger" wire:click="confirmDeleteBanner({{ $banner->id }})" />
                            </x-backoffice.management.actions>
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
            <flux:input wire:model="title" :label="__('Title')" required />
            <flux:input wire:model="subtitle" :label="__('Subtitle')" />
            <x-backoffice.form.toggle-field wire:model="showText" :label="__('Show title, subtitle and button on the slide (turn off when the image already has everything)')" />
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="link" :label="__('Link')" placeholder="/shop" />
                <flux:input wire:model="sortOrder" type="number" min="1" :label="__('Display Order')" />
                <flux:input wire:model="startsAt" type="datetime-local" :label="__('Show From')" />
                <flux:input wire:model="endsAt" type="datetime-local" :label="__('Show Until')" />
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
                        <x-ui.button type="button">{{ __('Close') }}</x-ui.button>
                    </flux:modal.close>
                    <x-ui.button variant="danger" wire:click="deleteBanner">{{ __('Delete') }}</x-ui.button>
                </div>
            </x-slot:footer>
        </x-backoffice.management.form-modal>

        <x-backoffice.management.details-modal wire:model="showDetails" :title="__('Banner Details')" :items="$details" />

        <x-backoffice.management.form-modal wire:model="showMediaUpload" :title="__('Upload media')" submit="saveMedia">
            @if ($this->uploadField)
                <x-media.upload :label="__('Banner image (wide, 1600×600 recommended)')" :field="$this->uploadField"
                    upload="imageUpload" remove="removeImage"
                    :selected="$imageUpload" :marked-for-removal="$removeImage" />
            @endif
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showMediaPreview" :title="__('Media')" :show-default-footer="false">
            @if ($this->previewField)
                <x-media.preview :label="__('Banner Image')" :field="$this->previewField" :action-label="__('View Image')" wire:click="viewImage" />
            @endif
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showImageViewer" :title="__('Banner image')" :show-default-footer="false">
            @if ($showImageViewer && $this->imageUrl)
                <img src="{{ $this->imageUrl }}" alt="{{ __('Banner image') }}"
                    class="mx-auto max-h-[75vh] w-auto rounded-lg object-contain shadow-sm" />
            @endif
        </x-backoffice.management.form-modal>
    </x-backoffice.layout.main-content-container>
</div>
