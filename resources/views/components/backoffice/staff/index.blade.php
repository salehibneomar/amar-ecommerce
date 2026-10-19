<div class="flex flex-col gap-6">
    <x-backoffice.layout.main-content-container :title="__('Manage Staff')" :subtitle="__('Staff sign in on the admin login page and see the sections their role allows.')">
        <x-backoffice.management.table :sortable="$this->tableSortableColumns" :columns="[
            'id' => __('ID'),
            'name' => __('Staff Member'),
            'role' => __('Role'),
            'tenure' => __('Joined'),
            'compensation' => __('Compensation'),
            'permissions' => __('Extra Permissions'),
            'status' => __('Status'),
            'actions' => __('Actions'),
        ]" :sort-by="$sortBy" :sort-direction="$sortDirection">
            <x-slot:toolbar>
                <div class="flex items-center gap-2">
                    <x-backoffice.management.per-page :placeholder="__('Select')" />
                </div>
                <div class="flex items-end gap-2">
                    <x-backoffice.management.search model="search" :placeholder="__('Search staff...')" :label="__('Search Staff')" class="max-w-xs" />
                    <x-ui.button variant="primary" wire:click="createStaff">{{ __('Add Staff') }}</x-ui.button>
                </div>
            </x-slot:toolbar>

            <x-slot:rows>
                @forelse ($this->staff as $member)
                    <flux:table.row wire:key="staff-{{ $member->id }}">
                        <flux:table.cell>{{ $member->id }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <x-ui.avatar size="sm" :name="$member->name" />
                                <div class="min-w-0">
                                    <p class="truncate font-medium">{{ $member->name }}</p>
                                    <p class="truncate text-xs text-zinc-500">{{ $member->email }}</p>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @foreach ($member->roles as $role)
                                <x-ui.badge color="violet">{{ $role->label }}</x-ui.badge>
                            @endforeach
                        </flux:table.cell>
                        <flux:table.cell>
                            <p>{{ $member->staffProfile?->joined_at?->format('d/m/Y') ?? appEmptyValue() }}</p>
                            @if ($member->staffProfile?->left_at)
                                <p class="text-xs text-zinc-500">{{ __('Left :date', ['date' => $member->staffProfile->left_at->format('d/m/Y')]) }}</p>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($profile = $member->staffProfile)
                                <p class="font-medium">{{ $profile->compensationLabel() }}</p>
                                <p class="text-xs text-zinc-500">{{ $profile->compensation_type->label() }}</p>
                            @else
                                <x-ui.empty-value />
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex max-w-xs flex-wrap gap-1">
                                @forelse ($member->permissions as $permission)
                                    <x-ui.badge color="green">{{ $this->permissionOptions[$permission->name] ?? $permission->name }}</x-ui.badge>
                                @empty
                                    <span class="text-xs text-zinc-400">{{ __('Role only') }}</span>
                                @endforelse
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <x-ui.badge :color="$member->status->color()">{{ $member->status->label() }}</x-ui.badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <x-backoffice.management.actions>
                                <x-backoffice.management.action icon="eye" :label="__('View')" variant="filled" wire:click="viewStaff({{ $member->id }})" />
                                <x-backoffice.management.action icon="pencil-square" :label="__('Edit')" variant="filled" color="sky" wire:click="editStaff({{ $member->id }})" />
                                <x-backoffice.management.action icon="key" :label="__('Permissions')" variant="filled" color="violet" wire:click="editPermissions({{ $member->id }})" />
                                <x-backoffice.management.action icon="banknotes" :label="__('Payments')" variant="filled" color="green" :href="route('manage.staff.payments', $member->id)" wire:navigate />
                                @if ($member->status->isActive())
                                    <x-backoffice.management.action icon="no-symbol" :label="__('Deactivate')" variant="danger" wire:click="confirmToggleStatus({{ $member->id }})" />
                                @else
                                    <x-backoffice.management.action icon="check-circle" :label="__('Activate')" variant="filled" wire:click="confirmToggleStatus({{ $member->id }})" />
                                @endif
                            </x-backoffice.management.actions>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8">{{ __('No staff yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-slot:rows>

            <x-slot:pagination><x-backoffice.management.pagination :paginator="$this->staff" /></x-slot:pagination>
        </x-backoffice.management.table>

        <x-backoffice.management.form-modal wire:model="showForm" size="2xl" :title="$editingStaffId ? __('Edit Staff') : __('Add Staff')" submit="saveStaff">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <flux:input wire:model="name" :label="__('Name')" required />
                <flux:input wire:model="email" type="email" :label="__('Email')" required />
                <flux:input wire:model="password" type="password" viewable :label="$editingStaffId ? __('New password') : __('Password')" :placeholder="$editingStaffId ? __('Leave empty to keep the current password') : null" autocomplete="new-password" />
                <flux:select wire:model.live="role" :label="__('Role')" required>
                    @foreach ($this->roleOptions as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            @if ($this->isAdminRole)
                <x-ui.alert icon="shield-check" :heading="__('Admins can do anything by default')" :text="__('A new admin gets every permission. Use Permissions to give a particular admin less.')" />
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 [&_input]:min-w-0">
                <flux:input wire:model="joinedAt" type="date" :label="__('Joining Date')" />
                <flux:input wire:model="leftAt" type="date" :label="__('Leaving Date')" />
            </div>

            <flux:radio.group wire:model.live="compensationType" :label="__('Compensation')" :description="__('Choose one: a fixed monthly salary or a share of the month\'s total sales.')" variant="segmented">
                @foreach ($this->compensationOptions as $value => $label)
                    <flux:radio :value="$value" :label="$label" />
                @endforeach
            </flux:radio.group>
            <flux:input wire:model="compensationValue" type="number" min="0" step="1" :max="$this->isPercentage ? 100 : null"
                :label="$this->isPercentage ? __('Percentage of monthly sales (%)') : __('Monthly salary (:symbol)', ['symbol' => appCurrencySymbol()])" required />
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showPermissions" size="2xl" :title="__('Permissions')" :subtitle="__('The role\'s default permissions are locked. Assign permissions for this staff only.')" submit="savePermissions">
            <x-backoffice.form.permission-checkboxes :groups="$this->permissionGroups" :locked="$rolePermissions" />
            <flux:error name="role" />
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showStatusConfirmation" size="md" :title="$statusStaffIsActive ? __('Deactivate staff member?') : __('Activate staff member?')">
            <flux:text>
                {{ $statusStaffIsActive
                    ? __('They can no longer sign in to the admin panel. Their role and payment history are kept.')
                    : __('They can sign in to the admin panel again with their current role.') }}
            </flux:text>
            <flux:error name="statusStaffId" />
            <flux:error name="role" />
            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <x-ui.button type="button">{{ __('Close') }}</x-ui.button>
                    </flux:modal.close>
                    <x-ui.button :variant="$statusStaffIsActive ? 'danger' : 'primary'" wire:click="toggleStatus">
                        {{ $statusStaffIsActive ? __('Deactivate') : __('Activate') }}
                    </x-ui.button>
                </div>
            </x-slot:footer>
        </x-backoffice.management.form-modal>

        <x-backoffice.management.details-modal wire:model="showDetails" size="2xl" :title="__('Staff Details')" :items="$details" :wide="[__('Extra permissions')]" />
    </x-backoffice.layout.main-content-container>
</div>
