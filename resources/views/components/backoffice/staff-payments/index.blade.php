<x-backoffice.layout.main-content-container
    :title="__('Payments of :name', ['name' => $staffName])"
    :subtitle="__('Monthly salary or sales-share records for this staff member.')"
    :breadcrumbs="[
        ['label' => __('Staff'), 'href' => route('manage.staff.index')],
        ['label' => $staffName],
        ['label' => __('Payments')],
    ]"
>
    <x-backoffice.management.table :sortable="$this->tableSortableColumns" :columns="[
        'period_year' => __('Period'),
        'basis' => __('Basis'),
        'amount' => __('Amount'),
        'paid_at' => __('Paid'),
        'actions' => __('Actions'),
    ]" :sort-by="$sortBy" :sort-direction="$sortDirection">
        <x-slot:toolbar>
            <div class="flex items-center gap-2">
                <x-backoffice.management.per-page :placeholder="__('Select')" />
            </div>
            <div class="flex items-end gap-2">
                <x-backoffice.management.search model="search" :placeholder="__('Search notes...')" :label="__('Search payments')" class="max-w-xs" />
                <flux:button variant="primary" icon="plus" wire:click="createPayment">{{ __('Record payment') }}</flux:button>
            </div>
        </x-slot:toolbar>

        <x-slot:rows>
            @forelse ($this->payments as $payment)
                <flux:table.row wire:key="payment-{{ $payment->id }}">
                    <flux:table.cell class="font-medium">{{ $payment->periodLabel() }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($payment->compensation_type_snapshot->isPercentage())
                            <p>{{ __(':value% of :sales', ['value' => $payment->compensation_value_snapshot, 'sales' => formatMoney($payment->total_sales_snapshot)]) }}</p>
                        @else
                            <p>{{ __('Salary :value', ['value' => formatMoney($payment->compensation_value_snapshot)]) }}</p>
                        @endif
                        @if ($payment->notes)
                            <p class="max-w-xs truncate text-xs text-zinc-500">{{ $payment->notes }}</p>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell class="font-semibold">{{ formatMoney($payment->amount) }}</flux:table.cell>
                    <flux:table.cell class="text-xs text-zinc-500">
                        {{ formatDateTime($payment->paid_at) }}
                        @if ($payment->paidBy)
                            <span class="block">{{ __('by :name', ['name' => $payment->paidBy->name]) }}</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="xs" variant="danger" wire:click="confirmDeletePayment({{ $payment->id }})">{{ __('Delete') }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __('No payments recorded yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </x-slot:rows>

        <x-slot:pagination><x-backoffice.management.pagination :paginator="$this->payments" /></x-slot:pagination>
    </x-backoffice.management.table>

    <x-backoffice.management.form-modal wire:model="showForm" size="lg" :title="__('Record payment')" submit="savePayment">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <flux:select wire:model.live="periodMonth" :label="__('Month')">
                @foreach ($this->monthOptions as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="periodYear" :label="__('Year')">
                @foreach ($this->yearOptions as $value)
                    <flux:select.option :value="$value">{{ $value }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        @if ($calculation = $this->calculation)
            <flux:callout icon="calculator" color="green">
                <flux:callout.heading>{{ __('Suggested: :amount', ['amount' => formatMoney($calculation['amount'])]) }}</flux:callout.heading>
                <flux:callout.text>
                    @if ($calculation['type']->isPercentage())
                        {{ __(':value% of :sales paid sales in this month.', ['value' => $calculation['value'], 'sales' => formatMoney($calculation['sales'])]) }}
                    @else
                        {{ __('Fixed monthly salary. Adjust for bonuses or deductions if needed.') }}
                    @endif
                </flux:callout.text>
            </flux:callout>
        @else
            <flux:callout icon="exclamation-triangle" color="amber" :text="__('This staff member has no compensation set. Edit the staff member first.')" />
        @endif

        <flux:input wire:model="amount" type="number" min="0" step="1" :label="__('Amount paid (:symbol)', ['symbol' => appCurrencySymbol()])" required />
        <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />
    </x-backoffice.management.form-modal>

    <x-backoffice.management.form-modal wire:model="showDeleteConfirmation" size="md" :title="__('Delete payment record?')">
        <flux:text>{{ __('This payment record will be deleted permanently.') }}</flux:text>
        <x-slot:footer>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button">{{ __('Close') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deletePayment">{{ __('Delete') }}</flux:button>
            </div>
        </x-slot:footer>
    </x-backoffice.management.form-modal>
</x-backoffice.layout.main-content-container>
