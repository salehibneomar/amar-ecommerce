<?php

namespace App\Enums\User;

enum PermissionEnum: string
{
    case ManageOrders = 'manage_orders';
    case ViewReports = 'view_reports';
    case ManagePaymentGateways = 'manage_payment_gateways';
    case ManageProducts = 'manage_products';
    case ManageCategories = 'manage_categories';
    case ManageBanners = 'manage_banners';
    case ManageFaqs = 'manage_faqs';
    case ManageOffers = 'manage_offers';
    case ManageCustomers = 'manage_customers';
    case ManageStaff = 'manage_staff';
    case ManageRoles = 'manage_roles';
    case ManageAnalytics = 'manage_analytics';
    case ManageSeoMeta = 'manage_seo_meta';
    case ManageSystem = 'manage_system';
    case ManageTheme = 'manage_theme';

    public function label(): string
    {
        return match ($this) {
            self::ManageOrders => __('Manage orders'),
            self::ViewReports => __('View sales reports'),
            self::ManagePaymentGateways => __('Manage payment gateways'),
            self::ManageProducts => __('Manage products'),
            self::ManageCategories => __('Manage categories'),
            self::ManageBanners => __('Manage banners'),
            self::ManageFaqs => __('Manage FAQs'),
            self::ManageOffers => __('Manage offers'),
            self::ManageCustomers => __('Manage customers'),
            self::ManageStaff => __('Manage staff and payments'),
            self::ManageRoles => __('Manage roles'),
            self::ManageAnalytics => __('Manage analytics'),
            self::ManageSeoMeta => __('Manage SEO'),
            self::ManageSystem => __('Manage system settings'),
            self::ManageTheme => __('Manage theme'),
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::ManageOrders, self::ViewReports, self::ManagePaymentGateways => __('Sales'),
            self::ManageProducts, self::ManageCategories => __('Catalog'),
            self::ManageBanners, self::ManageFaqs, self::ManageOffers => __('Content'),
            self::ManageCustomers, self::ManageStaff, self::ManageRoles => __('People'),
            self::ManageAnalytics, self::ManageSeoMeta => __('Marketing'),
            self::ManageSystem, self::ManageTheme => __('System'),
        };
    }

    public function isAdminOnly(): bool
    {
        return in_array($this, [self::ManageRoles, self::ManageSystem, self::ManageTheme], true);
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
