@props([
    'notifications' => [],
    'unreadCount' => 0,
    'allUrl',
    'bar' => false,
])

<x-ui.dropdown menu-class="w-80 max-w-[calc(100vw-2rem)]">
    <x-slot:trigger>
        <x-ui.button variant="subtle" square :bar="$bar" class="relative cursor-pointer max-sm:size-8!" aria-label="{{ __('Notifications') }}">
            <span class="material-symbols-outlined">notifications</span>
            <x-ui.counter :count="$unreadCount" />
        </x-ui.button>
    </x-slot:trigger>

    <div class="flex items-center justify-between px-2 py-1.5">
        <flux:heading size="sm">{{ __('Notifications') }}</flux:heading>
        @if ($unreadCount > 0)
            <x-ui.button size="xs" variant="ghost" wire:click="markAllNotifications(true)">
                {{ __('Mark All Read') }}</x-ui.button>
        @endif
    </div>
    <flux:menu.separator />

    @forelse ($notifications as $notification)
        @unless ($loop->first)
            <flux:menu.separator />
        @endunless
        <x-ui.menu-item wire:key="notification-{{ $notification['id'] }}"
            wire:click="openNotification('{{ $notification['id'] }}')" class="cursor-pointer items-start!">
            <x-notifications.item :notification="$notification" />
        </x-ui.menu-item>
    @empty
        <flux:text class="px-2 py-3 text-center">{{ __('No notifications yet.') }}</flux:text>
    @endforelse

    <flux:menu.separator />
    <x-ui.menu-item :href="$allUrl" class="justify-center! text-center text-xs!" wire:navigate>
        {{ __('View All Notifications') }}</x-ui.menu-item>
</x-ui.dropdown>
