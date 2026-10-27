<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Order;
use App\Services\Backoffice\Notification\NotificationService;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;

class NotificationSeeder extends Seeder
{
    public function run(NotificationService $notificationService): void
    {
        Order::query()->get()->each(fn (Order $order) => $notificationService->notifyOrder($order));
        Offer::query()->get()->each(fn (Offer $offer) => $notificationService->notifyOffer($offer));

        DatabaseNotification::query()->select('id')->get()->each(function (DatabaseNotification $notification, int $index): void {
            $createdAt = now()->subMinutes(fake()->numberBetween(5, 60 * 24 * 7));

            $notification->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
                'read_at' => $index % 2 === 0 ? $createdAt->copy()->addMinutes(fake()->numberBetween(1, 120)) : null,
            ])->save();
        });
    }
}
