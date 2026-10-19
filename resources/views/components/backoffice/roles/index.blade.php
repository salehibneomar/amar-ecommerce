<div class="flex flex-col gap-6">
    <x-backoffice.layout.main-content-container :title="__('Manage Roles')" :breadcrumbs="[['label' => __('Settings'), 'href' => route('settings.index')], ['label' => __('Roles')]]" :subtitle="__('Each role gives its staff a default set of permissions.')">
        <x-backoffice.management.table :sortable="$this->tableSortableColumns" :columns="[
            'id' => __('ID'),
            'name' => __('Role'),
            'permissions' => __('Permissions'),
            'staff' => __('Staff'),
            'actions' => __('Actions'),
        ]" :sort-by="$sortBy" :sort-direction="$sortDirection">
            <x-slot:toolbar>
                <div class="flex items-center gap-2">
                    <x-backoffice.management.per-page :placeholder="__('Select')" />
                </div>
                <div class="flex items-end gap-2">
                    <x-backoffice.management.search model="search" :placeholder="__('Search roles...')" :label="__('Search Roles')" class="max-w-xs" />
                    <x-ui.button variant="primary" wire:click="createRole">{{ __('Add Role') }}</x-ui.button>
                </div>
            </x-slot:toolbar>

            <x-slot:rows>
                @forelse ($this->roles as $role)
                    <flux:table.row wire:key="role-{{ $role->id }}">
                        <flux:table.cell>{{ $role->id }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="font-medium">{{ $role->label }}</span>
                            @if ($role->isSystem())
                                <x-ui.badge class="ms-1">{{ __('Built-in') }}</x-ui.badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex max-w-md flex-wrap gap-1">
                                @if ($role->isSuperAdmin())
                                    <x-ui.badge color="violet">{{ __('Everything') }}</x-ui.badge>
                                @elseif ($role->isAdmin())
                                    <x-ui.badge color="violet">{{ __('Set per admin') }}</x-ui.badge>
                                @else
                                    @forelse ($role->permissions as $permission)
                                        <x-ui.badge color="green">{{ $this->permissionOptions[$permission->name] ?? $permission->name }}</x-ui.badge>
                                    @empty
                                        <span class="text-xs text-zinc-400">{{ __('No access yet') }}</span>
                                    @endforelse
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $role->users_count }}</flux:table.cell>
                        <flux:table.cell>
                            <x-backoffice.management.actions>
                                @unless ($role->isLocked())
                                    <x-backoffice.management.action icon="pencil-square" :label="__('Edit')" variant="filled" color="sky" wire:click="editRole({{ $role->id }})" />
                                @endunless
                                @unless ($role->isSystem())
                                    <x-backoffice.management.action icon="trash" :label="__('Delete')" variant="danger" wire:click="confirmDeleteRole({{ $role->id }})" />
                                @endunless
                            </x-backoffice.management.actions>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">{{ __('No roles found.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-slot:rows>

            <x-slot:pagination><x-backoffice.management.pagination :paginator="$this->roles" /></x-slot:pagination>
        </x-backoffice.management.table>

        <x-backoffice.management.form-modal wire:model="showForm" size="2xl" :title="$editingRoleId ? __('Edit Role') : __('Add Role')" submit="saveRole">
            <flux:input wire:model="name" :label="__('Name')" required />

            <x-backoffice.form.permission-checkboxes :groups="$this->permissionGroups" />
        </x-backoffice.management.form-modal>

        <x-backoffice.management.form-modal wire:model="showDeleteConfirmation" size="md" :title="__('Delete role?')">
            <flux:text>{{ __('This role will be deleted permanently.') }}</flux:text>
            <flux:error name="role" />
            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <x-ui.button type="button">{{ __('Close') }}</x-ui.button>
                    </flux:modal.close>
                    <x-ui.button variant="danger" wire:click="deleteRole">{{ __('Delete') }}</x-ui.button>
                </div>
            </x-slot:footer>
        </x-backoffice.management.form-modal>
    </x-backoffice.layout.main-content-container>
</div>
