@props([
    'notifications' => [],
    'unreadCount' => 0,
    'allUrl',
    'inverse' => false,
])

<x-ui.dropdown menu-class="w-80 max-w-[calc(100vw-2rem)]">
    <x-slot:trigger>
        <flux:button variant="subtle" square @class([
            'relative cursor-pointer max-sm:size-8!',
            'text-white! hover:bg-white/15!' => $inverse,
        ]) aria-label="{{ __('Notifications') }}">
            <span class="material-symbols-outlined">notifications</span>
            @if ($unreadCount > 0)
                <span
                    class="absolute -end-0.5 -top-0.5 flex min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
            @endif
        </flux:button>
    </x-slot:trigger>

    <div class="flex items-center justify-between px-2 py-1.5">
        <flux:heading size="sm">{{ __('Notifications') }}</flux:heading>
        @if ($unreadCount > 0)
            <flux:button size="xs" variant="ghost" wire:click="markAllNotifications(true)">
                {{ __('Mark all read') }}</flux:button>
        @endif
    </div>
    <flux:menu.separator />

    @forelse ($notifications as $notification)
        @unless ($loop->first)
            <flux:menu.separator />
        @endunless
        <flux:menu.item wire:key="notification-{{ $notification['id'] }}"
            wire:click="openNotification('{{ $notification['id'] }}')" class="cursor-pointer items-start!">
            <x-notifications.item :notification="$notification" />
        </flux:menu.item>
    @empty
        <flux:text class="px-2 py-3 text-center">{{ __('No notifications yet.') }}</flux:text>
    @endforelse

    <flux:menu.separator />
    <flux:menu.item :href="$allUrl" class="justify-center! text-center text-xs!" wire:navigate>
        {{ __('View all notifications') }}</flux:menu.item>
</x-ui.dropdown>
