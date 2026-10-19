@props([
    'unreadCount' => 0,
])

<div {{ $attributes->class('flex flex-wrap items-center gap-2') }}>
    <x-ui.badge :color="$unreadCount ? 'sky' : 'zinc'">{{ trans_choice(':count unread|:count unread', $unreadCount, ['count' => $unreadCount]) }}</x-ui.badge>
    <flux:spacer />
    <x-ui.button size="xs" variant="primary" wire:click="markAllNotifications(true)">{{ __('Mark All as Read') }}</x-ui.button>
    <x-ui.button size="xs" wire:click="markAllNotifications(false)">{{ __('Mark All as Unread') }}</x-ui.button>
</div>
