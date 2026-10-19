<?php

namespace App\Enums\Order;

enum ContactModeEnum: string
{
    case Messenger = 'messenger';
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Messenger => __('Messenger'),
            self::Whatsapp => __('WhatsApp'),
            self::Email => __('Email'),
            self::Other => __('Others'),
        };
    }
}
