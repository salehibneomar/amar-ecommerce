<div
    x-data="{
        action: '',
        userId: null,
        userName: '',
        openConfirmation(action, userId, userName) {
            this.action = action;
            this.userId = userId;
            this.userName = userName;
            this.$wire.selectUserAction(userId);
        },
    }"
    class="flex flex-col gap-6"
>
    <x-backoffice.layout.main-content-container :title="__('Manage Users')" :subtitle="__('View and manage registered user accounts.')">
        <x-backoffice.management.table :sortable="$this->tableSortableColumns"
            :columns="[
                'id' => __('ID'),
                'name' => __('Name'),
                'email' => __('Email'),
                'created_at' => __('Registered'),
                'status' => __('Status'),
                'actions' => __('Actions'),
            ]"
            :sort-by="$sortBy"
            :sort-direction="$sortDirection"
        >
            <x-slot:toolbar>
                <div class="flex items-center gap-2">
                    <x-backoffice.management.per-page :placeholder="__('Select')" />
                    <x-backoffice.management.filter
                        model="status"
                        :label="__('Filter by Status')"
                        :options="$this->statusOptions"
                        :placeholder="__('All')"
                    />
                </div>
                <div class="ms-auto">
                    <x-backoffice.management.search
                        model="search"
                        :placeholder="__('Search users...')"
                        :label="__('Search Users')"
                    />
                </div>
            </x-slot:toolbar>

            <x-slot:rows>
                @forelse ($this->users as $user)
                    <flux:table.row wire:key="user-{{ $user->id }}">
                        <flux:table.cell>{{ $user->id }}</flux:table.cell>
                        <flux:table.cell>{{ $user->name }}</flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>{{ $user->created_at?->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>
                            <x-ui.badge :color="$user->status->color()">
                                {{ $user->status->label() }}
                            </x-ui.badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <x-backoffice.management.actions>
                                <x-backoffice.management.action icon="eye" :label="__('View')" variant="filled" wire:click="viewUser({{ $user->id }})" />
                                <x-backoffice.management.action
                                    :icon="$user->isBlocked() ? 'lock-open' : 'no-symbol'"
                                    :label="$user->isBlocked() ? __('Unblock') : __('Block')"
                                    :variant="$user->isBlocked() ? 'filled' : 'danger'"
                                    :color="$user->isBlocked() ? 'green' : null"
                                    x-on:click="openConfirmation('{{ $user->isBlocked() ? 'unblock' : 'block' }}', {{ $user->id }}, $el.dataset.userName)"
                                    data-user-name="{{ $user->name }}"
                                />
                            </x-backoffice.management.actions>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">{{ __('No users found.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-slot:rows>
            <x-slot:pagination>
                <x-backoffice.management.pagination :paginator="$this->users" />
            </x-slot:pagination>
        </x-backoffice.management.table>

        <x-backoffice.management.form-modal wire:model="showUserConfirmation" size="md">
            <x-slot:heading>
                <span x-text="action === 'block' ? '{{ __('Block user?') }}' : '{{ __('Unblock user?') }}'"></span>
            </x-slot:heading>

            <flux:text>
                <span x-text="action === 'block'
                    ? '{{ __('Are you sure you want to block') }}'
                    : '{{ __('Are you sure you want to unblock') }}'"></span>
                <span class="font-medium" x-text="userName"></span>?
            </flux:text>

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <x-ui.button type="button">{{ __('Close') }}</x-ui.button>
                    </flux:modal.close>
                    <x-ui.button type="button" wire:click="performUserAction" variant="danger">
                        {{ __('Confirm') }}
                    </x-ui.button>
                </div>
            </x-slot:footer>
        </x-backoffice.management.form-modal>

        <x-backoffice.management.details-modal wire:model="showDetails" size="2xl" :title="__('Customer Details')" :items="$details" :wide="[__('Address')]" />
    </x-backoffice.layout.main-content-container>
</div>
