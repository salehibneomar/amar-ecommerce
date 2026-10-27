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
                    <x-backoffice.management.filter model="status" :label="__('Filter by Status')" :options="$this->statusOptions" />
                </div>
                <div class="flex items-end gap-2">
                    <x-backoffice.management.search model="search" :placeholder="__('Search FAQs...')" :label="__('Search FAQs')" />
                    <x-ui.button variant="primary" wire:click="createFaq">{{ __('Add New') }}</x-ui.button>
                </div>
            </x-slot:toolbar>

            <x-slot:rows>
                @forelse ($this->faqs as $faq)
                    <flux:table.row wire:key="faq-{{ $faq->id }}">
                        <flux:table.cell>{{ $faq->id }}</flux:table.cell>
                        <flux:table.cell class="max-w-md truncate">{{ $faq->title }}</flux:table.cell>
                        <flux:table.cell>
                            <x-ui.badge :color="$faq->status->color()">{{ $faq->status->label() }}</x-ui.badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $faq->created_at?->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <x-backoffice.management.actions>
                                <x-backoffice.management.action icon="eye" :label="__('View')" variant="filled" wire:click="viewFaq({{ $faq->id }})" />
                                <x-backoffice.management.action icon="pencil-square" :label="__('Edit')" variant="filled" color="sky" wire:click="editFaq({{ $faq->id }})" />
                                <x-backoffice.management.action icon="trash" :label="__('Delete')" variant="danger" wire:click="confirmDeleteFaq({{ $faq->id }})" />
                            </x-backoffice.management.actions>
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
            <x-ui.select model="faqStatus" :label="__('Status')" :options="$this->statusOptions" />
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showDeleteConfirmation" size="md" :title="__('Delete FAQ?')">
            <flux:text>{{ __('This FAQ will be deleted permanently.') }}</flux:text>
            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <x-ui.button type="button">{{ __('Close') }}</x-ui.button>
                    </flux:modal.close>
                    <x-ui.button variant="danger" wire:click="deleteFaq">{{ __('Delete') }}</x-ui.button>
                </div>
            </x-slot:footer>
        </x-backoffice.management.form-modal>

        <x-backoffice.management.details-modal wire:model="showDetails" :title="__('FAQ Details')" :items="$details" />
    </x-backoffice.layout.main-content-container>
</div>
