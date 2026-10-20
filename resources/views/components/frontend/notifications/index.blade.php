<x-frontend.layout.page :title="__('Notifications')" :subtitle="__('Order updates and offers, newest first.')">
    <x-notifications.actions class="mb-4" :unread-count="$this->unreadCount" />

    @if ($this->notifications->isEmpty())
        <x-frontend.content.empty-state icon="notifications" :title="__('No notifications yet.')" />
    @else
        <x-ui.card as="ul" class="divide-y divide-line overflow-hidden">
            @foreach ($this->notifications as $notification)
                <li wire:key="notification-{{ $notification['id'] }}" @class(['flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center', 'bg-tint/50' => ! $notification['status']->isRead()])>
                    <button type="button" class="min-w-0 flex-1 cursor-pointer" wire:click="openNotification('{{ $notification['id'] }}')">
                        <x-notifications.item :notification="$notification" />
                    </button>
                    <x-ui.button size="xs" variant="ghost" class="self-end sm:self-auto" wire:click="toggleNotification('{{ $notification['id'] }}')">
                        {{ $notification['status']->toggleLabel() }}
                    </x-ui.button>
                </li>
            @endforeach
        </x-ui.card>
        <div class="mt-4">{{ $this->notifications->links() }}</div>
    @endif
</x-frontend.layout.page>
