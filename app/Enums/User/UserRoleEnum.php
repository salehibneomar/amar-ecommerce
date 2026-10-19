<?php

namespace App\Enums\User;

enum UserRoleEnum: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Moderator = 'moderator';
    case Editor = 'editor';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => __('Super Admin'),
            self::Admin => __('Admin'),
            self::Moderator => __('Moderator'),
            self::Editor => __('Editor'),
            self::User => __('Customer'),
        };
    }

    public function isLocked(): bool
    {
        return in_array($this, [self::SuperAdmin, self::Admin], true);
    }

    /**
     * @return list<PermissionEnum>
     */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::SuperAdmin, self::Admin => [],
            self::Moderator => [PermissionEnum::ManageOrders, PermissionEnum::ManageProducts, PermissionEnum::ManageCategories],
            self::Editor => [PermissionEnum::ManageFaqs],
            self::User => [],
        };
    }
}
