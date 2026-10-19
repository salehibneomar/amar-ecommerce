@props([
    'unreadCount' => 0,
])

<div {{ $attributes->class('flex flex-wrap items-center gap-2') }}>
    <flux:badge size="sm" :color="$unreadCount ? 'sky' : 'zinc'">{{ trans_choice(':count unread|:count unread', $unreadCount, ['count' => $unreadCount]) }}</flux:badge>
    <flux:spacer />
    <flux:button size="xs" variant="primary" wire:click="markAllNotifications(true)">{{ __('Mark all as read') }}</flux:button>
    <flux:button size="xs" wire:click="markAllNotifications(false)">{{ __('Mark all as unread') }}</flux:button>
</div>
