<?php

namespace App\Enums\Notification;

enum NotificationTypeEnum: string
{
    case OrderPlaced = 'order_placed';
    case OrderUpdated = 'order_updated';
    case OfferPublished = 'offer_published';

    public function label(): string
    {
        return match ($this) {
            self::OrderPlaced => __('New order'),
            self::OrderUpdated => __('Order update'),
            self::OfferPublished => __('Offer'),
        };
    }
}
