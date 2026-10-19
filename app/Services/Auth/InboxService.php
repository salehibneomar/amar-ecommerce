<?php

namespace App\Services\Auth;

use App\Enums\Notification\NotificationReadStatusEnum;
use App\Enums\Notification\NotificationTypeEnum;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Route;

class InboxService
{
    /**
     * @return list<array{id: string, label: string, message: string, status: NotificationReadStatusEnum, time: string, date: string}>
     */
    public function latest(int $userId, int $limit = 5): array
    {
        return array_values($this->inbox($userId)
            ->limit($limit)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => $this->present($notification))
            ->all());
    }

    /**
     * @return LengthAwarePaginator<int, array{id: string, label: string, message: string, status: NotificationReadStatusEnum, time: string, date: string}>
     */
    public function paginate(int $userId, string $search = '', ?NotificationReadStatusEnum $status = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->inbox($userId)
            ->when(filled($search), fn (Builder $query): Builder => $query->where('data', 'like', '%'.trim($search).'%'))
            ->when($status, fn (Builder $query, NotificationReadStatusEnum $status): Builder => $status === NotificationReadStatusEnum::Read ? $query->whereNotNull('read_at') : $query->whereNull('read_at'))
            ->paginate($perPage)
            ->through(fn (DatabaseNotification $notification): array => $this->present($notification));
    }

    public function countUnread(int $userId): int
    {
        return $this->inbox($userId)->whereNull('read_at')->count();
    }

    public function open(int $userId, string $id): string
    {
        $notification = $this->inbox($userId)->findOrFail($id);
        $notification->markAsRead();

        $route = (string) ($notification->data['route'] ?? '');

        return Route::has($route) ? route($route, $notification->data['route_params'] ?? []) : url()->previous();
    }

    public function toggleRead(int $userId, string $id): void
    {
        $notification = $this->inbox($userId)->findOrFail($id);
        $notification->read() ? $notification->markAsUnread() : $notification->markAsRead();
    }

    public function markAll(int $userId, bool $read): void
    {
        $this->inbox($userId)
            ->reorder()
            ->when($read, fn (Builder $query): Builder => $query->whereNull('read_at'), fn (Builder $query): Builder => $query->whereNotNull('read_at'))
            ->update(['read_at' => $read ? now() : null]);
    }

    /**
     * @return Builder<DatabaseNotification>
     */
    private function inbox(int $userId): Builder
    {
        return DatabaseNotification::query()
            ->select(['id', 'type', 'data', 'read_at', 'created_at'])
            ->where('notifiable_type', (new User)->getMorphClass())
            ->where('notifiable_id', $userId)
            ->latest();
    }

    /**
     * @return array{id: string, label: string, message: string, status: NotificationReadStatusEnum, time: string, date: string}
     */
    private function present(DatabaseNotification $notification): array
    {
        $type = NotificationTypeEnum::tryFrom($notification->type);

        return [
            'id' => $notification->id,
            'label' => $type?->label() ?? '',
            'message' => (string) __((string) ($notification->data['message'] ?? ''), $notification->data['params'] ?? []),
            'status' => NotificationReadStatusEnum::fromReadAt($notification->read_at),
            'time' => $notification->created_at?->diffForHumans() ?? '',
            'date' => formatDateTime($notification->created_at),
        ];
    }
}
