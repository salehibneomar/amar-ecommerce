<div class="flex flex-col gap-6">
    <x-backoffice.layout.main-content-container :title="__('Notifications')" :subtitle="__('Everything sent to you, newest first.')">
        <x-backoffice.management.table :columns="[
            'notification' => __('Notification'),
            'received' => __('Received'),
            'status' => __('Status'),
            'actions' => __('Actions'),
        ]">
            <x-slot:toolbar>
                <div class="flex items-center gap-2">
                    <x-backoffice.management.per-page :placeholder="__('Select')" />
                    <x-backoffice.management.filter model="status" :label="__('Filter by status')" :options="$this->statusOptions" />
                </div>
                <div class="flex items-end gap-2">
                    <x-backoffice.management.search model="search" :placeholder="__('Search notifications...')" :label="__('Search notifications')" class="max-w-xs" />
                </div>
            </x-slot:toolbar>

            <x-slot:actions>
                <x-notifications.actions class="w-full" :unread-count="$this->unreadCount" />
            </x-slot:actions>

            <x-slot:rows>
                @forelse ($this->notifications as $notification)
                    <flux:table.row wire:key="notification-{{ $notification['id'] }}">
                        <flux:table.cell class="min-w-72">
                            <button type="button" class="w-full cursor-pointer" wire:click="openNotification('{{ $notification['id'] }}')">
                                <x-notifications.item :notification="$notification" />
                            </button>
                        </flux:table.cell>
                        <flux:table.cell>{{ $notification['date'] }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$notification['status']->color()">{{ $notification['status']->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="xs" variant="filled" wire:click="toggleNotification('{{ $notification['id'] }}')">
                                {{ $notification['status']->toggleLabel() }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">{{ __('No notifications yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </x-slot:rows>

            <x-slot:pagination><x-backoffice.management.pagination :paginator="$this->notifications" /></x-slot:pagination>
        </x-backoffice.management.table>
    </x-backoffice.layout.main-content-container>
</div>
