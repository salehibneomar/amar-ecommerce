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
                    <x-backoffice.management.filter model="status" :label="__('Filter by Status')" :options="$this->statusOptions" />
                </div>
                <div class="flex items-end gap-2">
                    <x-backoffice.management.search model="search" :placeholder="__('Search notifications...')" :label="__('Search Notifications')" />
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
                            <x-ui.badge :color="$notification['status']->color()">{{ $notification['status']->label() }}</x-ui.badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <x-backoffice.management.action :icon="$notification['status']->isRead() ? 'envelope' : 'envelope-open'" :label="$notification['status']->toggleLabel()" wire:click="toggleNotification('{{ $notification['id'] }}')" />
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
