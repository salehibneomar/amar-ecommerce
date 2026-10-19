<?php

namespace Database\Seeders;

use App\Enums\User\PermissionEnum;
use App\Enums\User\UserRoleEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        foreach (UserRoleEnum::cases() as $role) {
            Role::findOrCreate($role->value)->syncPermissions(array_column($role->defaultPermissions(), 'value'));
        }
    }
}
