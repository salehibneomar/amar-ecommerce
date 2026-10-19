<?php

namespace App\Services\Backoffice\People;

use App\Enums\User\PermissionEnum;
use App\Enums\User\UserRoleEnum;
use App\Models\Role;
use App\Traits\Query\TableQueryTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleService
{
    use TableQueryTrait;

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'created_at'];
    }

    /**
     * @return LengthAwarePaginator<int, Role>
     */
    public function getRoles(string $search = '', int $perPage = 10, string $sortBy = 'id', string $sortDirection = 'asc'): LengthAwarePaginator
    {
        return $this->paginateTableQuery(
            query: $this->staffRoles()->with('permissions:id,name')->withCount('users'),
            search: $search,
            searchColumns: ['name', 'label'],
            filters: [],
            perPage: $perPage,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            sortableColumns: $this->sortableColumns(),
            defaultSortBy: 'id',
            defaultSortDirection: 'asc',
        );
    }

    public function getRoleById(int|string $id): Role
    {
        return $this->staffRoles()->with('permissions:id,name')->findOrFail($id);
    }

    /**
     * @return array{name: string, permissions: list<string>}
     */
    public function getRoleForm(int|string $id): array
    {
        $role = $this->getEditableRole($id);

        return [
            'name' => $role->label,
            'permissions' => $role->permissionNames(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getRoleOptions(bool $includeAdmin): array
    {
        return $this->staffRoles()
            ->where('name', '!=', UserRoleEnum::SuperAdmin->value)
            ->when(! $includeAdmin, fn (Builder $query): Builder => $query->where('name', '!=', UserRoleEnum::Admin->value))
            ->orderBy('id')
            ->get(['id', 'name', 'label'])
            ->mapWithKeys(fn (Role $role): array => [$role->name => $role->label])
            ->all();
    }

    /**
     * @param  list<string>  $permissions
     */
    public function save(?int $id, string $name, array $permissions): Role
    {
        $role = $id ? $this->getEditableRole($id) : new Role(['name' => Str::slug($name, '_') ?: 'role_'.Str::lower(Str::random(8))]);
        $role->label = trim($name);
        $role->save();
        $role->syncPermissions(array_values(array_filter(
            $permissions,
            fn (string $permission): bool => PermissionEnum::isGrantableValue($permission),
        )));

        return $role;
    }

    public function delete(int|string $id): void
    {
        $role = $this->getRoleById($id)->loadCount('users');

        if ($role->isSystem()) {
            throw ValidationException::withMessages(['role' => __('Built-in roles cannot be deleted.')]);
        }

        if ($role->users_count > 0) {
            throw ValidationException::withMessages(['role' => __('Move the staff in this role to another role first.')]);
        }

        $role->delete();
    }

    private function getEditableRole(int|string $id): Role
    {
        $role = $this->getRoleById($id);

        if ($role->isLocked()) {
            throw ValidationException::withMessages(['name' => __('This role cannot be changed.')]);
        }

        return $role;
    }

    /**
     * @return Builder<Role>
     */
    private function staffRoles(): Builder
    {
        return Role::query()->where('name', '!=', UserRoleEnum::User->value);
    }
}
