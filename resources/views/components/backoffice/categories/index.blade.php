<div class="flex flex-col gap-6">
    <x-backoffice.layout.main-content-container :title="__('Manage Categories')" :subtitle="__('Organise books into categories and sub-categories.')">
        <x-backoffice.management.table :sortable="$this->tableSortableColumns" :columns="[
            'id' => __('ID'),
            'name' => __('Name'),
            'image' => __('Image'),
            'parent' => __('Parent'),
            'sort_order' => __('Order'),
            'status' => __('Status'),
            'actions' => __('Actions'),
        ]" :sort-by="$sortBy" :sort-direction="$sortDirection">
            <x-slot:toolbar>
                <div class="flex items-center gap-2">
                    <x-backoffice.management.per-page :placeholder="__('Select')" />
                    <x-backoffice.management.filter model="status" :label="__('Filter by status')" :options="$this->statusOptions" />
                    <x-backoffice.management.category-level-select model="categoryFilter" :levels="$this->categoryFilterOptions" :labels="[__('Sub-categories of'), __('Sub-category')]" filter />
                    <div class="flex shrink-0 items-center md:h-10">
                        <flux:switch wire:model.live="rootOnly" :label="__('Root Categories')" align="left" />
                    </div>
                </div>
                <div class="flex items-end gap-2">
                    <x-backoffice.management.search model="search" :placeholder="__('Search categories...')" :label="__('Search categories')"
                        class="max-w-xs" />
                    <flux:button variant="primary" wire:click="createCategory">{{ __('Add New') }}</flux:button>
                </div>
            </x-slot:toolbar>

            <x-slot:rows>
                @forelse ($this->categories as $category)
                    <flux:table.row wire:key="category-{{ $category->id }}">
                        <flux:table.cell>{{ $category->id }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $category->name }}</span>
                                @if ($category->is_featured)
                                    <flux:badge size="sm" color="amber">{{ __('Featured') }}</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($category->image_thumbnail_url)
                                <img src="{{ $category->image_thumbnail_url }}" alt="{{ $category->name }}" width="40" height="40" loading="lazy" class="size-10 rounded object-cover ring-1 ring-zinc-950/10" />
                            @else
                                <span class="text-zinc-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $category->parent?->name ?? __('Root') }}</flux:table.cell>
                        <flux:table.cell>{{ $category->sort_order }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$category->status->color()">
                                {{ $category->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-2">
                                @if ($category->image_thumbnail_url)
                                    <flux:button size="xs" variant="filled" color="green"
                                        wire:click="viewMedia({{ $category->id }})">{{ __('Media') }}</flux:button>
                                @endif
                                <flux:button size="xs" variant="filled" color="violet"
                                    wire:click="uploadMedia({{ $category->id }})">{{ __('Upload') }}</flux:button>
                                <flux:button size="xs" variant="filled"
                                    wire:click="viewCategory({{ $category->id }})">{{ __('View') }}</flux:button>
                                <flux:button size="xs" variant="filled" color="sky"
                                    wire:click="editCategory({{ $category->id }})">{{ __('Edit') }}</flux:button>
                                <flux:button size="xs" variant="danger"
                                    wire:click="confirmDeleteCategory({{ $category->id }})">{{ __('Delete') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">{{ __('No categories found.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-slot:rows>

            <x-slot:pagination><x-backoffice.management.pagination :paginator="$this->categories" /></x-slot:pagination>
        </x-backoffice.management.table>

        <x-backoffice.management.form-modal wire:model="showForm" :title="$editingCategoryId ? __('Edit Category') : __('Add Category')" submit="saveCategory">
            <flux:input wire:model="name" :label="__('Name')" required />
            <div class="grid grid-cols-1 gap-4 md:auto-cols-fr md:grid-flow-col">
                <x-backoffice.management.category-level-select model="parentLevels" :levels="$this->parentLevelOptions" :labels="[__('Parent category'), __('Sub-category')]" :placeholder="__('None (Root)')" />
            </div>
            <flux:textarea wire:model="description" :label="__('Description')" rows="3" />
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:select wire:model="categoryStatus" :label="__('Status')">
                    @foreach ($this->statusOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="sortOrder" type="number" min="1" :label="__('Display order')" />
            </div>
            <x-backoffice.form.toggle-field wire:model="isFeatured" :label="__('Featured category')" />
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showDeleteConfirmation" size="md" :title="__('Delete category?')">
            <flux:text>{{ __('Books in this category will become uncategorised.') }}</flux:text>
            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="deleteCategory">{{ __('Delete') }}</flux:button>
                </div>
            </x-slot:footer>
        </x-backoffice.management.form-modal>

        <x-backoffice.management.details-modal wire:model="showDetails" :title="__('Category Details')" :items="$details" />

        <x-backoffice.management.form-modal wire:model="showMediaUpload" :title="__('Upload media')" submit="saveMedia">
            @if ($this->uploadField)
                <x-media.upload :label="__('Category image (square, 600×600 recommended)')" :field="$this->uploadField"
                    upload="imageUpload" remove="removeImage"
                    :selected="$imageUpload" :marked-for-removal="$removeImage" />
            @endif
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showMediaPreview" :title="__('Media')" :show-default-footer="false">
            @if ($this->previewField)
                <x-media.preview :label="__('Category image')" :field="$this->previewField" :action-label="__('View image')" wire:click="viewImage" />
            @endif
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showImageViewer" :title="__('Category image')" :show-default-footer="false">
            @if ($showImageViewer && $this->imageUrl)
                <img src="{{ $this->imageUrl }}" alt="{{ __('Category image') }}"
                    class="mx-auto max-h-[75vh] w-auto rounded-lg object-contain shadow-sm" />
            @endif
        </x-backoffice.management.form-modal>
    </x-backoffice.layout.main-content-container>
</div>
