<div class="flex flex-col gap-6">
    <x-backoffice.layout.main-content-container :title="__('Manage Products')" :subtitle="__('Create and manage the product catalog.')">
        <x-backoffice.management.table :sortable="$this->tableSortableColumns" :columns="[
            'id' => __('ID'),
            'sku' => __('SKU'),
            'name' => __('Name'),
            'image' => __('Image'),
            'quantity' => __('Stock'),
            'price' => __('Price'),
            'category' => __('Category'),
            'status' => __('Status'),
            'created_at' => __('Created'),
            'actions' => __('Actions'),
        ]" :sort-by="$sortBy" :sort-direction="$sortDirection">
            <x-slot:toolbar>
                <div class="flex items-center gap-2">
                    <x-backoffice.management.per-page :placeholder="__('Select')" />
                    <x-backoffice.management.filter model="status" :label="__('Filter by Status')" :options="$this->statusOptions" />
                    <x-backoffice.management.category-level-select model="categoryFilter" :levels="$this->categoryFilterOptions" :labels="[__('Filter by category'), __('Sub-category'), __('Sub-sub-category')]" filter />
                </div>
                <div class="flex items-end gap-2">
                    <x-backoffice.management.search model="search" :placeholder="__('Search products...')" :label="__('Search Products')" />
                    <x-ui.button variant="primary" wire:click="createProduct">
                        {{ __('Add New') }}
                    </x-ui.button>
                </div>
            </x-slot:toolbar>

            <x-slot:rows>
                @forelse ($this->products as $product)
                    <flux:table.row wire:key="product-{{ $product->id }}">
                        <flux:table.cell>{{ $product->id }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">{{ $product->sku }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="max-w-xs truncate font-medium">{{ $product->name }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($product->thumbnail_url)
                                <img src="{{ $product->thumbnail_url }}" alt="{{ $product->name }}"
                                    width="40" height="40" loading="lazy" class="size-10 rounded object-cover ring-1 ring-zinc-950/10" />
                            @else
                                <x-ui.empty-value />
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $product->quantity }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <x-ui.price :amount="$product->final_price" :original="$product->has_discount ? $product->price : null" size="sm" plain />
                        </flux:table.cell>
                        <flux:table.cell>{{ $product->category?->name ?? appEmptyValue() }}</flux:table.cell>
                        <flux:table.cell>
                            <x-ui.badge :color="$product->status->color()">{{ $product->status->label() }}</x-ui.badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $product->created_at?->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <x-backoffice.management.actions>
                                <x-backoffice.management.action icon="eye" :label="__('Details')" variant="filled" wire:click="viewProduct({{ $product->id }})" />
                                <x-backoffice.management.action icon="pencil-square" :label="__('Edit')" variant="filled" color="sky" wire:click="editProduct({{ $product->id }})" />
                                @if ($product->has_any_media)
                                    <x-backoffice.management.action icon="photo" :label="__('Images')" variant="filled" color="green" wire:click="viewMedia({{ $product->id }})" />
                                @endif
                                <x-backoffice.management.action icon="arrow-up-tray" :label="__('Upload')" variant="filled" color="violet" wire:click="uploadMedia({{ $product->id }})" />
                                <x-backoffice.management.action icon="trash" :label="__('Delete')" variant="danger" wire:click="confirmDeleteProduct({{ $product->id }})" />
                            </x-backoffice.management.actions>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="9">{{ __('No products found.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-slot:rows>

            <x-slot:pagination>
                <x-backoffice.management.pagination :paginator="$this->products" />
            </x-slot:pagination>
        </x-backoffice.management.table>

        <x-backoffice.management.form-modal wire:model="showForm" size="2xl" :title="$editingProductId ? __('Edit Product') : __('Add Product')" submit="saveProduct">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="name" :label="__('Name')" required />
                <flux:input wire:model="sku" :label="__('SKU')" :placeholder="__('e.g. TSHIRT-BLK-M')" required />
            </div>
            @if (categoryLevel() === 1)
                <x-backoffice.management.category-level-select model="categoryLevels" :levels="$this->categoryLevelOptions" :placeholder="__('Choose a category')" required />
            @endif
            @if (categoryLevel() > 1)
                <div @class(['grid grid-cols-1 gap-4', 'md:grid-cols-2' => categoryLevel() === 2, 'md:grid-cols-3' => categoryLevel() === 3])>
                    <x-backoffice.management.category-level-select model="categoryLevels" :levels="$this->categoryLevelOptions" :placeholder="__('Choose a category')" required />
                </div>
            @endif
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <flux:select wire:model="productStatus" :label="__('Status')">
                    @foreach ($this->statusOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="badge" :label="__('Badge')">
                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                    @foreach ($this->badgeOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="quantity" type="number" min="0" step="1" :label="__('Quantity in Stock')" required />
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="price" type="number" min="0" step="1" :label="__('Price (:symbol)', ['symbol' => appCurrencySymbol()])" required />
                <flux:input wire:model="discountPercentage" type="number" min="0" max="100" step="1" :label="__('Discount (%)')" />
            </div>
            <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                <x-backoffice.form.toggle-field wire:model="isFeatured" :label="__('Featured Product')" />
            </div>
            <x-form.rich-text-editor wire:model="description" :label="__('Description')" />
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showMediaUpload" size="2xl"
            :title="$this->mediaProduct ? __('Upload images: :product', ['product' => $this->mediaProduct->name]) : __('Upload images')"
            submit="saveMedia">
            @if ($this->uploadGallery)
                <x-media.gallery-upload :label="__('Product Images')" :gallery="$this->uploadGallery" upload="imageUploads" />
            @endif
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showMediaPreview" size="2xl"
            :title="$this->previewProduct ? __('Images: :product', ['product' => $this->previewProduct->name]) : __('Images')"
            :show-default-footer="false">
            @if ($this->previewProduct)
                <x-media.gallery wire:key="product-preview-gallery-{{ $previewProductId }}" :images="$this->previewProduct->gallery_images" :alt="$this->previewProduct->name"
                    :zoomable="false" :first-label="__('Thumbnail')" thumbnail-action="setPreviewThumbnail" class="mx-auto w-full max-w-md">
                    <x-slot:empty>
                        <div class="flex h-64 flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-line text-zinc-400">
                            <flux:icon.photo class="size-8" />
                            <p class="text-sm">{{ __('Not uploaded') }}</p>
                        </div>
                    </x-slot:empty>
                </x-media.gallery>
            @endif
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showDetails" size="2xl" :title="__('Product Details')"
            :show-default-footer="false">
            <div class="space-y-6">
                <div>
                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-100" style="font-size: 13pt;">
                        {{ $this->viewingProduct?->name }}
                    </h3>
                </div>

                <dl
                    class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if ($this->viewingProduct?->status)
                                <x-ui.badge :color="$this->viewingProduct->status->color()">
                                    {{ $this->viewingProduct->status->label() }}
                                </x-ui.badge>
                            @else
                                <x-ui.empty-value />
                            @endif
                        </dd>
                    </div>

                    @if ($this->viewingProduct?->badge)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Badge') }}</dt>
                            <dd class="mt-1">
                                <x-ui.badge :color="$this->viewingProduct->badge->color()">
                                    {{ $this->viewingProduct->badge->label() }}
                                </x-ui.badge>
                            </dd>
                        </div>
                    @endif

                    <x-backoffice.management.detail-item :label="__('SKU')">{{ $this->viewingProduct?->sku ?? appEmptyValue() }}</x-backoffice.management.detail-item>
                    <x-backoffice.management.detail-item :label="__('Price')">
                        @if ($this->viewingProduct)
                            <x-ui.price :amount="$this->viewingProduct->final_price" :original="$this->viewingProduct->has_discount ? $this->viewingProduct->price : null" size="sm" plain />
                        @endif
                    </x-backoffice.management.detail-item>
                    <x-backoffice.management.detail-item :label="__('Category')">{{ $this->viewingProductCategory ?? appEmptyValue() }}</x-backoffice.management.detail-item>
                    <x-backoffice.management.detail-item :label="__('Last Updated by')">{{ $this->viewingProduct?->lastUpdateLabel() }}</x-backoffice.management.detail-item>

                    <x-backoffice.management.detail-item :label="__('Quantity in Stock')">{{ $this->viewingProduct?->quantity ?? appEmptyValue() }}</x-backoffice.management.detail-item>

                    <x-backoffice.management.detail-item :label="__('Created')">{{ formatDateTime($this->viewingProduct?->created_at) ?: appEmptyValue() }}</x-backoffice.management.detail-item>
                </dl>

                @if ($this->viewingProduct?->description)
                    <div>
                        <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</dt>
                        <dd class="rich-text mt-1 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
                            {!! $this->viewingProduct->description !!}
                        </dd>
                    </div>
                @endif
            </div>

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <x-ui.button type="button">{{ __('Close') }}</x-ui.button>
                    </flux:modal.close>
                    @if ($this->viewingProduct)
                        <x-ui.button variant="filled" color="sky"
                            wire:click="editProduct({{ $this->viewingProduct->id }})">
                            {{ __('Edit') }}
                        </x-ui.button>
                    @endif
                </div>
            </x-slot:footer>
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showDeleteConfirmation" size="md" :title="__('Delete product?')">
            <flux:text>{{ __('This product will be moved to the trash.') }}</flux:text>

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <x-ui.button type="button">{{ __('Close') }}</x-ui.button>
                    </flux:modal.close>
                    <x-ui.button variant="danger" wire:click="deleteProduct">
                        {{ __('Delete') }}
                    </x-ui.button>
                </div>
            </x-slot:footer>
        </x-backoffice.management.form-modal>
    </x-backoffice.layout.main-content-container>
</div>
