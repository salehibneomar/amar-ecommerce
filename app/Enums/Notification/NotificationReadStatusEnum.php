<?php

namespace App\Enums\Notification;

enum NotificationReadStatusEnum: string
{
    case Unread = 'unread';
    case Read = 'read';

    public function label(): string
    {
        return match ($this) {
            self::Unread => __('Unread'),
            self::Read => __('Read'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unread => 'sky',
            self::Read => 'zinc',
        };
    }

    public function isRead(): bool
    {
        return $this === self::Read;
    }

    public function toggleLabel(): string
    {
        return match ($this) {
            self::Unread => __('Mark as read'),
            self::Read => __('Mark as unread'),
        };
    }

    public static function fromReadAt(mixed $readAt): self
    {
        return $readAt === null ? self::Unread : self::Read;
    }
}
