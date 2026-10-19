<?php

namespace App\Services\Backoffice\People;

use App\Enums\User\PermissionEnum;
use App\Enums\User\StaffCompensationTypeEnum;
use App\Enums\User\UserRoleEnum;
use App\Enums\User\UserStatusEnum;
use App\Models\Role;
use App\Models\User;
use App\Traits\Query\TableQueryTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffService
{
    use TableQueryTrait;

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'email', 'status', 'created_at'];
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function getStaff(int $exceptId, string $search = '', int $perPage = 10, string $sortBy = 'created_at', string $sortDirection = 'desc'): LengthAwarePaginator
    {
        return $this->paginateTableQuery(
            query: User::query()->staff()->whereKeyNot($exceptId)->withoutRole(UserRoleEnum::SuperAdmin->value)->with(['staffProfile', 'roles:id,name', 'permissions:id,name']),
            search: $search,
            searchColumns: ['name', 'email'],
            filters: [],
            perPage: $perPage,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            sortableColumns: $this->sortableColumns(),
        );
    }

    public function getStaffById(int|string $id): User
    {
        return User::query()->staff()->with(['staffProfile', 'roles.permissions:id,name', 'permissions:id,name'])->findOrFail($id);
    }

    /**
     * @return array{is_admin: bool, role: list<string>, direct: list<string>}
     */
    public function getPermissionForm(int|string $id): array
    {
        $member = $this->getStaffById($id);
        $this->ensureCanManage($member);

        return [
            'is_admin' => $member->isAdmin(),
            'role' => array_values(array_unique($member->roles->flatMap(fn (Role $role): array => $role->permissionNames())->all())),
            'direct' => array_column($member->permissions->all(), 'name'),
        ];
    }

    /**
     * @param  array{name: string, email: string, password: string|null, role: string, compensation_type: string, compensation_value: string|int, joined_at: string|null, left_at: string|null}  $data
     */
    public function save(?int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data): User {
            $member = $id ? $this->getStaffById($id) : new User;

            $this->ensureCanAssign($member, $data['role']);

            $member->fill(['name' => $data['name'], 'email' => $data['email']]);

            if (filled($data['password'])) {
                $member->password = $data['password'];
            }

            if (! $member->exists) {
                $member->status = UserStatusEnum::Active;
                $member->email_verified_at = now();
            }

            $becomesAdmin = $data['role'] === UserRoleEnum::Admin->value && ! $member->isAdmin();
            $leavesAdmin = $data['role'] !== UserRoleEnum::Admin->value && $member->exists && $member->isAdmin();

            $member->save();
            $member->syncRoles([$data['role']]);

            if ($becomesAdmin || $leavesAdmin) {
                $member->syncPermissions($becomesAdmin ? array_column(PermissionEnum::cases(), 'value') : []);
            }

            $member->staffProfile()->updateOrCreate([], [
                'compensation_type' => StaffCompensationTypeEnum::from($data['compensation_type']),
                'compensation_value' => (int) $data['compensation_value'],
                'joined_at' => $data['joined_at'] ?: null,
                'left_at' => $data['left_at'] ?: null,
            ]);

            return $member;
        });
    }

    /**
     * @param  list<string>  $permissions
     */
    public function syncPermissions(int|string $id, array $permissions): User
    {
        $member = $this->getStaffById($id);
        $this->ensureCanManage($member);

        return $member->syncPermissions(array_values(array_filter(
            $permissions,
            fn (string $permission): bool => PermissionEnum::isGrantableValue($permission, toAdmin: $member->isAdmin()),
        )));
    }

    public function toggleStatus(int|string $id): User
    {
        $member = $this->getStaffById($id);

        if ($member->is(Auth::user())) {
            throw ValidationException::withMessages(['statusStaffId' => __('You cannot deactivate your own account.')]);
        }

        $this->ensureCanManage($member);

        $member->update(['status' => $member->status->toggled()]);

        return $member;
    }

    private function ensureCanAssign(User $member, string $role): void
    {
        $this->ensureCanManage($member);

        $assignable = ! in_array($role, [UserRoleEnum::User->value, UserRoleEnum::SuperAdmin->value], true)
            && ($role !== UserRoleEnum::Admin->value || Auth::user()?->isSuperAdmin());

        if (! $assignable) {
            throw ValidationException::withMessages(['role' => __('You cannot assign this role.')]);
        }

        if ($member->is(Auth::user()) && ! $member->hasRole($role)) {
            throw ValidationException::withMessages(['role' => __('You cannot change your own role.')]);
        }
    }

    private function ensureCanManage(User $member): void
    {
        if ($member->exists && $member->isSuperAdmin()) {
            throw ValidationException::withMessages(['role' => __('The Super Admin cannot be changed here.')]);
        }

        if ($member->exists && $member->isAdmin() && ! Auth::user()?->isSuperAdmin()) {
            throw ValidationException::withMessages(['role' => __('Only the Super Admin can change admins.')]);
        }
    }
}
