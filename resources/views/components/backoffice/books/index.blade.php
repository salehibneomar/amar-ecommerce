<div class="flex flex-col gap-6">
    <x-backoffice.layout.main-content-container :title="__('Manage Books')" :subtitle="__('Create and manage books catalog.')">
        <x-backoffice.management.table :sortable="$this->tableSortableColumns" :columns="[
            'id' => __('ID'),
            'name' => __('Name'),
            'cover' => __('Cover'),
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
                    <x-backoffice.management.search model="search" :placeholder="__('Search books...')" :label="__('Search Books')"
                        class="max-w-xs" />
                    <x-ui.button variant="primary" wire:click="createBook">
                        {{ __('Add New') }}
                    </x-ui.button>
                </div>
            </x-slot:toolbar>

            <x-slot:rows>
                @forelse ($this->books as $book)
                    <flux:table.row wire:key="book-{{ $book->id }}">
                        <flux:table.cell>{{ $book->id }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="max-w-xs truncate font-medium">{{ $book->name }}</div>
                            <div class="truncate text-xs text-zinc-500">{{ $book->author }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($book->cover_thumbnail_url)
                                <img src="{{ $book->cover_thumbnail_url }}" alt="{{ $book->name }}"
                                    width="32" height="48" loading="lazy" class="h-12 w-8 rounded object-cover ring-1 ring-zinc-950/10" />
                            @else
                                <x-ui.empty-value />
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <x-ui.price :amount="$book->final_price" :original="$book->has_discount ? $book->price : null" :free="$book->is_free" size="sm" />
                        </flux:table.cell>
                        <flux:table.cell>{{ $book->category?->name ?? appEmptyValue() }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                <x-ui.badge :color="$book->status->color()">{{ $book->status->label() }}</x-ui.badge>
                                @if ($this->canChooseStockType)
                                    <x-ui.badge :color="$book->stock_type->color()">{{ $book->stock_type->label() }}</x-ui.badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $book->created_at?->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <x-backoffice.management.actions>
                                <x-backoffice.management.action icon="eye" :label="__('Details')" variant="filled" wire:click="viewBook({{ $book->id }})" />
                                <x-backoffice.management.action icon="pencil-square" :label="__('Edit')" variant="filled" color="sky" wire:click="editBook({{ $book->id }})" />
                                @if ($book->has_any_media)
                                    <x-backoffice.management.action icon="photo" :label="__('Media')" variant="filled" color="green" wire:click="viewMedia({{ $book->id }})" />
                                @endif
                                <x-backoffice.management.action icon="arrow-up-tray" :label="__('Upload')" variant="filled" color="violet" wire:click="uploadMedia({{ $book->id }})" />
                                <x-backoffice.management.action icon="trash" :label="__('Delete')" variant="danger" wire:click="confirmDeleteBook({{ $book->id }})" />
                            </x-backoffice.management.actions>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8">{{ __('No books found.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-slot:rows>

            <x-slot:pagination>
                <x-backoffice.management.pagination :paginator="$this->books" />
            </x-slot:pagination>
        </x-backoffice.management.table>

        <x-backoffice.management.form-modal wire:model="showForm" size="2xl" :title="$editingBookId ? __('Edit Book') : __('Add Book')" submit="saveBook">
            <div @class(['grid grid-cols-1 gap-4', 'md:grid-cols-2' => categoryLevel() === 3])>
                <flux:input wire:model="name" :label="__('Title')" required />
                @if (categoryLevel() === 3)
                    <flux:input wire:model="author" :label="__('Author')" />
                @endif
            </div>
            <div @class(['grid grid-cols-1 gap-4', 'md:grid-cols-2' => categoryLevel() === 1, 'md:grid-cols-3' => categoryLevel() > 1])>
                @if (categoryLevel() < 3)
                    <flux:input wire:model="author" :label="__('Author')" />
                @endif
                <x-backoffice.management.category-level-select model="categoryLevels" :levels="$this->categoryLevelOptions" :placeholder="__('Choose a category')" required />
            </div>
            <div @class([
                'grid grid-cols-1 gap-4',
                'md:grid-cols-3' => ! $this->canChooseStockType && ! $this->isPhysicalStock,
                'md:grid-cols-4' => $this->canChooseStockType !== $this->isPhysicalStock,
                'md:grid-cols-5' => $this->canChooseStockType && $this->isPhysicalStock,
            ])>
                <flux:select wire:model="bookStatus" :label="__('Status')">
                    @foreach ($this->statusOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if ($this->canChooseStockType)
                    <flux:select wire:model.live="bookStockType" :label="__('Stock Type')">
                        @foreach ($this->stockTypeOptions as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <flux:select wire:model="badge" :label="__('Badge')">
                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                    @foreach ($this->badgeOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="pages" type="number" min="1" :label="__('Pages')" />
                @if ($this->isPhysicalStock)
                    <flux:input wire:model="quantity" type="number" min="0" :label="__('Quantity in Stock')" />
                @endif
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="price" type="number" min="0" step="1" :label="__('Price (:symbol)', ['symbol' => appCurrencySymbol()])" :disabled="$isFree" />
                <flux:input wire:model="discountPercentage" type="number" min="0" max="100" :label="__('Discount (%)')" :disabled="$isFree" />
            </div>
            <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                @unless ($this->isPhysicalOnly)
                    <x-backoffice.form.toggle-field wire:model.live="isFree" :label="__('Free Book')" />
                @endunless
                <x-backoffice.form.toggle-field wire:model="isFeatured" :label="__('Featured Book')" />
            </div>
            <x-form.rich-text-editor wire:model="description" :label="__('Description')" />
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showMediaUpload" size="4xl"
            :title="$this->mediaBook ? __('Upload media: :book', ['book' => $this->mediaBook->name]) : __('Upload media')"
            submit="saveMedia">
            @if ($this->uploadFields)
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-media.upload :label="__('Cover Image')" :field="$this->uploadFields['cover']"
                        upload="coverUpload" remove="removeCover"
                        :selected="$coverUpload" :marked-for-removal="$removeCover" />
                    <x-media.upload :label="$this->isPhysicalOnly ? __('Preview PDF (sample pages)') : __('Book PDF')" :field="$this->uploadFields['file']"
                        upload="fileUpload" remove="removeFile"
                        :selected="$fileUpload" :marked-for-removal="$removeFile" />
                </div>
            @endif
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showMediaPreview" size="2xl"
            :title="$this->previewBook ? __('Media: :book', ['book' => $this->previewBook->name]) : __('Media')"
            :show-default-footer="false">
            @if ($this->previewFields)
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-media.preview :label="__('Cover Image')" :field="$this->previewFields['cover']"
                        :action-label="__('View Image')" wire:click="viewImage" />
                    <x-media.preview :label="$this->isPhysicalOnly ? __('Preview PDF') : __('Book PDF')" :field="$this->previewFields['file']"
                        :action-label="__('Open PDF')" wire:click="viewPdf({{ $previewBookId }})" />
                </div>
            @endif
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showImageViewer" size="4xl" :title="__('Cover image')"
            :show-default-footer="false">
            @if ($showImageViewer && $this->coverImageUrl)
                <img src="{{ $this->coverImageUrl }}" alt="{{ $this->previewBook?->name }}"
                    class="mx-auto max-h-[75vh] w-auto rounded-lg object-contain shadow-sm" />
            @endif
        </x-backoffice.management.form-modal>

        <x-media.pdf-viewer-modal wire:model="showPdfViewer" :title="__('PDF viewer')"
            :document-id="$showPdfViewer && $pdfBookId ? (string) $pdfBookId : null" :viewer-key="$pdfViewerKey">
            @if ($this->pdfDownloadUrl)
                <x-slot:actions>
                    <x-ui.button size="sm" variant="ghost" icon="arrow-down-tray" download
                        :href="$this->pdfDownloadUrl">
                        {{ __('Download') }}
                    </x-ui.button>
                </x-slot:actions>
            @endif
        </x-media.pdf-viewer-modal>

        <x-backoffice.management.form-modal wire:model="showDetails" size="2xl" :title="__('Book Details')"
            :show-default-footer="false">
            <div class="space-y-6">
                <div>
                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-100" style="font-size: 13pt;">
                        {{ $this->viewingBook?->name }}
                    </h3>
                    @if ($this->viewingBook?->author)
                        <flux:subheading class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Author: :author', ['author' => $this->viewingBook->author]) }}
                        </flux:subheading>
                    @endif
                </div>

                <dl
                    class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if ($this->viewingBook?->status)
                                <x-ui.badge :color="$this->viewingBook->status->color()">
                                    {{ $this->viewingBook->status->label() }}
                                </x-ui.badge>
                            @else
                                <x-ui.empty-value />
                            @endif
                        </dd>
                    </div>

                    @if ($this->canChooseStockType)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Stock Type') }}</dt>
                            <dd class="mt-1">
                                @if ($this->viewingBook?->stock_type)
                                    <x-ui.badge :color="$this->viewingBook->stock_type->color()">
                                        {{ $this->viewingBook->stock_type->label() }}
                                    </x-ui.badge>
                                @else
                                    <x-ui.empty-value />
                                @endif
                            </dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Access') }}</dt>
                        <dd class="mt-1">
                            <x-ui.badge :color="$this->viewingBook?->is_free ? 'green' : 'zinc'">
                                {{ $this->viewingBook?->is_free ? __('Free') : __('Paid') }}
                            </x-ui.badge>
                        </dd>
                    </div>

                    @if ($this->viewingBook?->badge)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Badge') }}</dt>
                            <dd class="mt-1">
                                <x-ui.badge :color="$this->viewingBook->badge->color()">
                                    {{ $this->viewingBook->badge->label() }}
                                </x-ui.badge>
                            </dd>
                        </div>
                    @endif

                    <x-backoffice.management.detail-item :label="__('Price')">
                        @if ($this->viewingBook)
                            <x-ui.price :amount="$this->viewingBook->final_price" :original="$this->viewingBook->has_discount ? $this->viewingBook->price : null" :free="$this->viewingBook->is_free" size="sm" plain />
                        @endif
                    </x-backoffice.management.detail-item>
                    <x-backoffice.management.detail-item :label="__('Category')">{{ $this->viewingBookCategory ?? appEmptyValue() }}</x-backoffice.management.detail-item>
                    <x-backoffice.management.detail-item :label="__('Pages')">{{ $this->viewingBook?->pages ?: appEmptyValue() }}</x-backoffice.management.detail-item>
                    <x-backoffice.management.detail-item :label="__('Last Updated by')">{{ $this->viewingBook?->lastUpdateLabel() }}</x-backoffice.management.detail-item>

                    @if ($this->viewingBookIsPhysical)
                        <x-backoffice.management.detail-item :label="__('Quantity in Stock')">{{ $this->viewingBook?->quantity ?? appEmptyValue() }}</x-backoffice.management.detail-item>
                    @endif

                    <x-backoffice.management.detail-item :label="__('Created')">{{ formatDateTime($this->viewingBook?->created_at) ?: appEmptyValue() }}</x-backoffice.management.detail-item>
                </dl>

                @if ($this->viewingBook?->description)
                    <div>
                        <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</dt>
                        <dd class="rich-text mt-1 text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
                            {!! $this->viewingBook->description !!}
                        </dd>
                    </div>
                @endif
            </div>

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <x-ui.button type="button">{{ __('Close') }}</x-ui.button>
                    </flux:modal.close>
                    @if ($this->viewingBook)
                        <x-ui.button variant="filled" color="sky"
                            wire:click="editBook({{ $this->viewingBook->id }})">
                            {{ __('Edit') }}
                        </x-ui.button>
                    @endif
                </div>
            </x-slot:footer>
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showDeleteConfirmation" size="md" :title="__('Delete book?')">
            <flux:text>{{ __('This book will be moved to the trash.') }}</flux:text>

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <x-ui.button type="button">{{ __('Close') }}</x-ui.button>
                    </flux:modal.close>
                    <x-ui.button variant="danger" wire:click="deleteBook">
                        {{ __('Delete') }}
                    </x-ui.button>
                </div>
            </x-slot:footer>
        </x-backoffice.management.form-modal>
    </x-backoffice.layout.main-content-container>
</div>
