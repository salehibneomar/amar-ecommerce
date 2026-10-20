<?php

namespace App\Enums\User;

enum PermissionEnum: string
{
    case ManageOrders = 'manage_orders';
    case ViewReports = 'view_reports';
    case ManageProducts = 'manage_products';
    case ManageCategories = 'manage_categories';
    case ManageBanners = 'manage_banners';
    case ManageFaqs = 'manage_faqs';
    case ManageOffers = 'manage_offers';
    case ManageCustomers = 'manage_customers';
    case ManageStaff = 'manage_staff';
    case ManageRoles = 'manage_roles';
    case ManageSystem = 'manage_system';

    public function label(): string
    {
        return match ($this) {
            self::ManageOrders => __('Manage orders'),
            self::ViewReports => __('View sales reports'),
            self::ManageProducts => __('Manage products'),
            self::ManageCategories => __('Manage categories'),
            self::ManageBanners => __('Manage banners'),
            self::ManageFaqs => __('Manage FAQs'),
            self::ManageOffers => __('Manage offers'),
            self::ManageCustomers => __('Manage customers'),
            self::ManageStaff => __('Manage staff and payments'),
            self::ManageRoles => __('Manage roles'),
            self::ManageSystem => __('Manage system settings'),
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::ManageOrders, self::ViewReports => __('Sales'),
            self::ManageProducts, self::ManageCategories => __('Catalog'),
            self::ManageBanners, self::ManageFaqs, self::ManageOffers => __('Content'),
            self::ManageCustomers, self::ManageStaff, self::ManageRoles => __('People'),
            self::ManageSystem => __('System'),
        };
    }

    public function isAdminOnly(): bool
    {
        return in_array($this, [self::ManageRoles, self::ManageSystem], true);
    }

    /**
     * @return list<self>
     */
    public static function grantable(bool $toAdmin = false): array
    {
        return array_values(array_filter(self::cases(), fn (self $permission): bool => $toAdmin || ! $permission->isAdminOnly()));
    }

    public static function isGrantableValue(string $permission, bool $toAdmin = false): bool
    {
        $case = self::tryFrom($permission);

        return $case !== null && ($toAdmin || ! $case->isAdminOnly());
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(array_map(fn (self $permission): array => [$permission->value, $permission->label()], self::cases()), 1, 0);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function groupedOptions(bool $toAdmin = false): array
    {
        $groups = [];

        foreach (self::grantable($toAdmin) as $permission) {
            $groups[$permission->group()][$permission->value] = $permission->label();
        }

        return $groups;
    }
}
