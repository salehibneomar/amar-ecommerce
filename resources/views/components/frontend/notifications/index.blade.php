<x-frontend.layout.page :title="__('Notifications')" :subtitle="__('Order updates and offers, newest first.')">
    <x-notifications.actions class="mb-4" :unread-count="$this->unreadCount" />

    @if ($this->notifications->isEmpty())
        <div class="flex flex-col items-center gap-4 rounded-xl border border-dashed border-zinc-300 px-6 py-16 text-center dark:border-zinc-700">
            <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('No notifications yet.') }}</p>
        </div>
    @else
        <x-ui.card as="ul" class="divide-y divide-line overflow-hidden">
            @foreach ($this->notifications as $notification)
                <li wire:key="notification-{{ $notification['id'] }}" @class(['flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center', 'bg-brand-50/60 dark:bg-brand-950/30' => ! $notification['status']->isRead()])>
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
