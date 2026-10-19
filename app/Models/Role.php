<?php

namespace App\Models;

use App\Enums\User\UserRoleEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property string $label
 * @property-read int|null $users_count
 */
class Role extends SpatieRole
{
    public function isSystem(): bool
    {
        return UserRoleEnum::tryFrom($this->name) !== null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->name === UserRoleEnum::SuperAdmin->value;
    }

    public function isAdmin(): bool
    {
        return $this->name === UserRoleEnum::Admin->value;
    }

    public function isLocked(): bool
    {
        return (bool) UserRoleEnum::tryFrom($this->name)?->isLocked();
    }

    /**
     * @return list<string>
     */
    public function permissionNames(): array
    {
        return array_values($this->permissions->map(fn (Permission $permission): string => $permission->name)->all());
    }

    /**
     * @return Attribute<string, string>
     */
    protected function label(): Attribute
    {
        return Attribute::get(fn (?string $label): string => $label ?? UserRoleEnum::tryFrom($this->name)?->label() ?? $this->name);
    }
}
