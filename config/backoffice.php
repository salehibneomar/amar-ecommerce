<?php

use App\Enums\User\PermissionEnum;

return [
    'route_prefix' => trim((string) env('ADMIN_ROUTE_PREFIX', 'backoffice'), '/') ?: 'backoffice',

    'navigation' => [
        [
            'heading' => 'Sales',
            'items' => [
                ['label' => 'Orders', 'icon' => 'shopping-bag', 'route' => 'manage.orders.index', 'active' => 'manage.orders.*', 'permission' => PermissionEnum::ManageOrders->value],
                ['label' => 'Sales Reports', 'icon' => 'chart-bar', 'route' => 'manage.sales-reports.index', 'active' => 'manage.sales-reports.*', 'permission' => PermissionEnum::ViewReports->value],
            ],
        ],
        [
            'heading' => 'Catalog',
            'items' => [
                ['label' => 'Books', 'icon' => 'book-open', 'route' => 'manage.books.index', 'active' => 'manage.books.*', 'permission' => PermissionEnum::ManageProducts->value],
                ['label' => 'Categories', 'icon' => 'squares-2x2', 'route' => 'manage.categories.index', 'active' => 'manage.categories.*', 'permission' => PermissionEnum::ManageCategories->value],
            ],
        ],
        [
            'heading' => 'Shop Content',
            'items' => [
                ['label' => 'Banners', 'icon' => 'photo', 'route' => 'manage.banners.index', 'active' => 'manage.banners.*', 'permission' => PermissionEnum::ManageBanners->value],
                ['label' => 'FAQs', 'icon' => 'question-mark-circle', 'route' => 'manage.faqs.index', 'active' => 'manage.faqs.*', 'permission' => PermissionEnum::ManageFaqs->value],
            ],
        ],
        [
            'heading' => 'People',
            'items' => [
                ['label' => 'Customers', 'icon' => 'users', 'route' => 'manage.users.index', 'active' => 'manage.users.*', 'permission' => PermissionEnum::ManageCustomers->value],
                ['label' => 'Staff', 'icon' => 'user-group', 'route' => 'manage.staff.index', 'active' => 'manage.staff.*', 'permission' => PermissionEnum::ManageStaff->value],
            ],
        ],
    ],
];
