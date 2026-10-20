<?php

namespace App\Services\Backoffice\Sales;

use App\Enums\Catalog\ProductStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Enums\User\PermissionEnum;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    private const RECENT_LIMIT = 5;

    public function __construct(private readonly OrderService $orderService) {}

    /**
     * @return array{stats: list<array{icon: string, label: string, value: string, trend: string|null, trendUp: bool}>, panels: array<string, mixed>}
     */
    public function forUser(User $user): array
    {
        $stats = [
            [PermissionEnum::ManageCustomers, fn (): array => $this->customerStat()],
            [PermissionEnum::ManageOrders, fn (): array => $this->orderStat()],
            [PermissionEnum::ViewReports, fn (): array => $this->revenueStat()],
            [PermissionEnum::ManageProducts, fn (): array => $this->productStat()],
        ];
        $panels = [
            'orders' => [PermissionEnum::ManageOrders, fn (): array => ['pipeline' => $this->getOrderPipeline(), 'recent' => $this->getRecentOrders()]],
            'registrations' => [PermissionEnum::ManageCustomers, fn (): array => $this->getRecentUsers()],
            'inventory' => [PermissionEnum::ManageProducts, fn (): array => $this->getProductInventory()],
        ];
        $dashboard = ['stats' => [], 'panels' => []];

        foreach ($stats as [$permission, $build]) {
            if ($user->can($permission->value)) {
                $dashboard['stats'][] = $build();
            }
        }

        foreach ($panels as $key => [$permission, $build]) {
            if ($user->can($permission->value)) {
                $dashboard['panels'][$key] = $build();
            }
        }

        return $dashboard;
    }

    /**
     * @return array{icon: string, label: string, value: string, trend: string|null, trendUp: bool}
     */
    private function customerStat(): array
    {
        $users = $this->customers()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as joined_this_month', [now()->startOfMonth()])
            ->toBase()
            ->first();

        return $this->stat('users', __('Customers'), (int) $users->total, (int) $users->joined_this_month);
    }

    /**
     * @return array{icon: string, label: string, value: string, trend: string|null, trendUp: bool}
     */
    private function orderStat(): array
    {
        return [
            'icon' => 'shopping-bag',
            'label' => __('Orders this month'),
            'value' => number_format(Order::query()->where('created_at', '>=', now()->startOfMonth())->count()),
            'trend' => null,
            'trendUp' => true,
        ];
    }

    /**
     * @return array{icon: string, label: string, value: string, trend: string|null, trendUp: bool}
     */
    private function revenueStat(): array
    {
        $monthStart = now()->startOfMonth();
        $totals = Order::query()
            ->where('payment_status', PaymentStatusEnum::Paid)
            ->where('created_at', '>=', $monthStart->subMonth())
            ->selectRaw('sum(case when created_at >= ? then total else 0 end) as this_month', [$monthStart])
            ->selectRaw('sum(case when created_at < ? then total else 0 end) as last_month', [$monthStart])
            ->toBase()
            ->first();
        $thisMonth = (int) $totals->this_month;
        $lastMonth = (int) $totals->last_month;

        return [
            'icon' => 'banknotes',
            'label' => __('Revenue this month'),
            'value' => formatMoney($thisMonth),
            'trend' => $lastMonth > 0 ? $this->revenueTrend($thisMonth, $lastMonth) : null,
            'trendUp' => $thisMonth >= $lastMonth,
        ];
    }

    /**
     * @return array{icon: string, label: string, value: string, trend: string|null, trendUp: bool}
     */
    private function productStat(): array
    {
        $products = Product::query()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as added_this_month', [now()->startOfMonth()])
            ->toBase()
            ->first();

        return $this->stat('cube', __('Products'), (int) $products->total, (int) $products->added_this_month);
    }

    /**
     * @return array{total: int, pending: int, stages: array<int, array{label: string, value: int, percent: int, color: string}>}
     */
    public function getOrderPipeline(): array
    {
        $counts = Order::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $total = (int) $counts->sum();

        return [
            'total' => $total,
            'pending' => $this->orderService->countUnresolved(),
            'stages' => array_map(fn (OrderStatusEnum $status): array => [
                'label' => $status->label(),
                'value' => (int) ($counts[$status->value] ?? 0),
                'percent' => $this->percent((int) ($counts[$status->value] ?? 0), $total),
                'color' => $status->dotClass(),
            ], OrderStatusEnum::cases()),
        ];
    }

    /**
     * @return array<int, array{order_id: string, email: string, amount: string, payment: string, payment_color: string, time: string}>
     */
    public function getRecentOrders(): array
    {
        return Order::query()
            ->latest()
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get(['id', 'user_id', 'public_order_id', 'total', 'payment_status', 'created_at'])
            ->map(fn (Order $order): array => [
                'order_id' => $order->public_order_id,
                'email' => $order->customer_email,
                'amount' => formatMoney($order->total),
                'payment' => $order->payment_status->label(),
                'payment_color' => $order->payment_status->color(),
                'time' => $order->created_at->diffForHumans(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{name: string, email: string, joined: string, status: string, status_color: string}>
     */
    public function getRecentUsers(): array
    {
        return $this->customers()
            ->latest()
            ->orderByDesc('id')
            ->limit(self::RECENT_LIMIT)
            ->get(['name', 'email', 'status', 'created_at'])
            ->map(fn (User $user): array => [
                'name' => $user->name,
                'email' => $user->email,
                'joined' => $user->created_at?->diffForHumans() ?? '',
                'status' => $user->status->label(),
                'status_color' => $user->status->color(),
            ])
            ->all();
    }

    /**
     * @return array<int, array{label: string, value: int, percent: int}>
     */
    public function getProductInventory(): array
    {
        $counts = Product::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $total = (int) $counts->sum();

        return array_map(fn (ProductStatusEnum $status): array => [
            'label' => $status->label(),
            'value' => (int) ($counts[$status->value] ?? 0),
            'percent' => $this->percent((int) ($counts[$status->value] ?? 0), $total),
        ], ProductStatusEnum::cases());
    }

    /**
     * @return Builder<User>
     */
    private function customers(): Builder
    {
        return User::query()->customers();
    }

    /**
     * @return array{icon: string, label: string, value: string, trend: string|null, trendUp: bool}
     */
    private function stat(string $icon, string $label, int $total, int $addedThisMonth): array
    {
        return [
            'icon' => $icon,
            'label' => $label,
            'value' => number_format($total),
            'trend' => $addedThisMonth > 0 ? $this->addedTrend($addedThisMonth) : null,
            'trendUp' => true,
        ];
    }

    private function addedTrend(int $count): string
    {
        return __('+:count this month', ['count' => number_format($count)]);
    }

    private function revenueTrend(int $thisMonth, int $lastMonth): string
    {
        return __(':percent% vs last month', ['percent' => sprintf('%+d', round(($thisMonth - $lastMonth) / $lastMonth * 100))]);
    }

    private function percent(int $value, int $total): int
    {
        return $total > 0 ? (int) round($value / $total * 100) : 0;
    }
}
