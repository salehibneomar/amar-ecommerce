<?php

namespace App\Services\Backoffice\Notification;

use App\Enums\Content\OfferTypeEnum;
use App\Enums\Notification\NotificationTypeEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\User\PermissionEnum;
use App\Enums\User\UserRoleEnum;
use App\Enums\User\UserStatusEnum;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NotificationService
{
    public function notifyOrder(Order $order): void
    {
        $actor = Auth::user();
        $actorName = $actor instanceof User && $actor->isBackofficeUser() ? $actor->name : null;
        $status = strtolower($order->status->label());

        if ($order->status === OrderStatusEnum::Requested) {
            $this->send($this->staffWith(PermissionEnum::ManageOrders), NotificationTypeEnum::OrderPlaced, 'New order #:id was placed.', ['id' => $order->public_order_id], 'manage.orders.index', ['order' => $order->id]);

            return;
        }

        $this->send(
            $this->staffWith(PermissionEnum::ManageOrders, $actor instanceof User ? $actor->id : null),
            NotificationTypeEnum::OrderUpdated,
            $actorName ? 'Order #:id was marked :status by :name.' : 'Order #:id was marked :status.',
            ['id' => $order->public_order_id, 'status' => $status, 'name' => (string) $actorName],
            'manage.orders.index',
            ['order' => $order->id],
        );

        if ($order->user_id) {
            $this->send(User::query()->whereKey($order->user_id), NotificationTypeEnum::OrderUpdated, 'Your order #:id is now :status.', ['id' => $order->public_order_id, 'status' => $status], 'user.orders.detail', ['order' => $order->public_order_id]);
        }
    }

    public function notifyOffer(Offer $offer): void
    {
        $offer->loadMissing('categories:id,name');

        $this->send(
            User::query()->customers()->where('status', UserStatusEnum::Active),
            NotificationTypeEnum::OfferPublished,
            $offer->type === OfferTypeEnum::Global ? ':name: :discount% off everything in the shop.' : ':name: :discount% off :categories.',
            ['name' => $offer->name, 'discount' => $offer->discount_percentage, 'categories' => $offer->categories->pluck('name')->join(', ')],
            'shop',
            [],
        );
    }

    /**
     * @return Builder<User>
     */
    private function staffWith(PermissionEnum $permission, ?int $exceptUserId = null): Builder
    {
        return User::query()
            ->where('status', UserStatusEnum::Active)
            ->where(fn (Builder $query) => $query
                ->role(UserRoleEnum::SuperAdmin->value)
                ->orWhere(fn (Builder $query) => $query->permission($permission->value)))
            ->when($exceptUserId, fn (Builder $query, int $userId) => $query->whereKeyNot($userId));
    }

    /**
     * @param  Builder<User>  $recipients
     * @param  array<string, string|int>  $params
     * @param  array<string, string|int>  $routeParams
     */
    private function send(Builder $recipients, NotificationTypeEnum $type, string $message, array $params, string $route, array $routeParams): void
    {
        $data = json_encode(['message' => $message, 'params' => $params, 'route' => $route, 'route_params' => $routeParams]);
        $morphClass = (new User)->getMorphClass();

        $recipients->select('id')->chunkById(500, function (Collection $users) use ($type, $data, $morphClass): void {
            $now = now();

            DatabaseNotification::query()->insert($users->map(fn (User $user): array => [
                'id' => (string) Str::uuid(),
                'type' => $type->value,
                'notifiable_type' => $morphClass,
                'notifiable_id' => $user->id,
                'data' => $data,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });
    }
}
