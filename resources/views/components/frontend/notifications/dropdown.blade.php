<div wire:poll.{{ notificationLongPollSeconds() }}s>
    <x-notifications.dropdown :notifications="$this->notifications" :unread-count="$this->unreadCount" :all-url="route('user.notifications')" bar />
</div>
