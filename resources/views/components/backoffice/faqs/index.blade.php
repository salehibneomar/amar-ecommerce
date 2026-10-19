<div class="flex flex-col gap-6">
    <x-backoffice.layout.main-content-container :title="__('Manage FAQs')" :subtitle="__('Create and manage frequently asked questions.')">
        <x-backoffice.management.table :sortable="$this->tableSortableColumns" :columns="[
            'id' => __('ID'),
            'title' => __('Title'),
            'status' => __('Status'),
            'created_at' => __('Created'),
            'actions' => __('Actions'),
        ]" :sort-by="$sortBy" :sort-direction="$sortDirection">
            <x-slot:toolbar>
                <div class="flex items-center gap-2">
                    <x-backoffice.management.per-page :placeholder="__('Select')" />
                    <x-backoffice.management.filter model="status" :label="__('Filter by status')" :options="$this->statusOptions" />
                </div>
                <div class="flex items-end gap-2">
                    <x-backoffice.management.search model="search" :placeholder="__('Search FAQs...')" :label="__('Search FAQs')" class="max-w-xs" />
                    <flux:button variant="primary" wire:click="createFaq">{{ __('Add New') }}</flux:button>
                </div>
            </x-slot:toolbar>

            <x-slot:rows>
                @forelse ($this->faqs as $faq)
                    <flux:table.row wire:key="faq-{{ $faq->id }}">
                        <flux:table.cell>{{ $faq->id }}</flux:table.cell>
                        <flux:table.cell class="max-w-md truncate">{{ $faq->title }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$faq->status->color()">{{ $faq->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $faq->created_at?->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button size="xs" variant="filled" wire:click="viewFaq({{ $faq->id }})">{{ __('View') }}</flux:button>
                                <flux:button size="xs" variant="filled" color="sky" wire:click="editFaq({{ $faq->id }})">{{ __('Edit') }}</flux:button>
                                <flux:button size="xs" variant="danger" wire:click="confirmDeleteFaq({{ $faq->id }})">{{ __('Delete') }}</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">{{ __('No FAQs found.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-slot:rows>

            <x-slot:pagination><x-backoffice.management.pagination :paginator="$this->faqs" /></x-slot:pagination>
        </x-backoffice.management.table>

        <x-backoffice.management.form-modal wire:model="showForm" :title="$editingFaqId ? __('Edit FAQ') : __('Add FAQ')" submit="saveFaq">
            <flux:input wire:model="title" :label="__('Title')" />
            <flux:textarea wire:model="description" :label="__('Description')" rows="5" />
            <flux:select wire:model="faqStatus" :label="__('Status')">
                @foreach ($this->statusOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showDeleteConfirmation" size="md" :title="__('Delete FAQ?')">
            <flux:text>{{ __('This FAQ will be deleted permanently.') }}</flux:text>
            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button">{{ __('Close') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="danger" wire:click="deleteFaq">{{ __('Delete') }}</flux:button>
                </div>
            </x-slot:footer>
        </x-backoffice.management.form-modal>

        <x-backoffice.management.details-modal wire:model="showDetails" :title="__('FAQ Details')" :items="$details" />
    </x-backoffice.layout.main-content-container>
</div>
