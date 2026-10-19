<?php

namespace App\Services\Backoffice\People;

use App\Enums\User\UserStatusEnum;
use App\Models\User;
use App\Traits\Query\TableQueryTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    use TableQueryTrait;

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'email', 'created_at', 'status'];
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function getAllUsers(
        string $search = '',
        int $perPage = 10,
        ?UserStatusEnum $status = null,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
    ): LengthAwarePaginator {
        $query = User::query()->customers();

        return $this->paginateTableQuery(
            query: $query,
            search: $search,
            searchColumns: ['name', 'email'],
            filters: ['status' => $status],
            perPage: $perPage,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            sortableColumns: $this->sortableColumns(),
        );
    }

    public function getUserById(int|string $id): User
    {
        return User::findOrFail($id);
    }

    public function block(User $user): bool
    {
        return $this->updateStatus($user, UserStatusEnum::Blocked);
    }

    public function unblock(User $user): bool
    {
        return $this->updateStatus($user, UserStatusEnum::Active);
    }

    public function updateStatus(User $user, UserStatusEnum $status): bool
    {
        return $user->update(['status' => $status]);
    }
}
