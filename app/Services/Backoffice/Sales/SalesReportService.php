<?php

namespace App\Services\Backoffice\Sales;

use App\Enums\Order\PaymentStatusEnum;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class SalesReportService
{
    private const TOP_PRODUCTS_LIMIT = 10;

    /**
     * @return array{orders: int, revenue: int, discount: int, items: int, average: int}
     */
    public function getSummary(int $month, int $year): array
    {
        $orders = $this->paidOrders($month, $year)
            ->selectRaw('count(*) as orders')
            ->selectRaw('coalesce(sum(total), 0) as revenue')
            ->selectRaw('coalesce(sum(discount_amount + extra_discount_amount), 0) as discount')
            ->toBase()
            ->first();
        $items = (int) OrderItem::query()
            ->whereHas('order', fn (Builder $query): Builder => $this->scopePaid($query, $month, $year))
            ->sum('quantity');
        $count = (int) $orders->orders;
        $revenue = (int) $orders->revenue;

        return [
            'orders' => $count,
            'revenue' => $revenue,
            'discount' => (int) $orders->discount,
            'items' => $items,
            'average' => $count > 0 ? (int) round($revenue / $count) : 0,
        ];
    }

    /**
     * @return list<array{date: string, orders: int, revenue: int}>
     */
    public function getDailySales(int $month, int $year): array
    {
        $rows = $this->paidOrders($month, $year)
            ->selectRaw('date(created_at) as day, count(*) as orders, sum(total) as revenue')
            ->groupBy('day')
            ->orderBy('day')
            ->toBase()
            ->get();

        return array_values($rows->map(fn (object $row): array => [
            'date' => CarbonImmutable::parse($row->day)->format('d M, D'),
            'orders' => (int) $row->orders,
            'revenue' => (int) $row->revenue,
        ])->all());
    }

    /**
     * @return list<array{name: string, quantity: int, revenue: int}>
     */
    public function getTopProducts(int $month, int $year): array
    {
        return array_values(OrderItem::query()
            ->whereHas('order', fn (Builder $query): Builder => $this->scopePaid($query, $month, $year))
            ->selectRaw('product_name_snapshot as name, sum(quantity) as quantity, sum(line_total) as revenue')
            ->groupBy('product_name_snapshot')
            ->orderByDesc('quantity')
            ->limit(self::TOP_PRODUCTS_LIMIT)
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'name' => (string) $row->name,
                'quantity' => (int) $row->quantity,
                'revenue' => (int) $row->revenue,
            ])
            ->all());
    }

    /**
     * @return list<array{label: string, revenue: int}>
     */
    public function getMonthlyRevenue(int $year): array
    {
        return array_map(fn (int $month): array => [
            'label' => CarbonImmutable::create($year, $month, 1)->format('M'),
            'revenue' => (int) $this->paidOrders($month, $year)->sum('total'),
        ], range(1, 12));
    }

    /**
     * @return Builder<Order>
     */
    private function paidOrders(int $month, int $year): Builder
    {
        return $this->scopePaid(Order::query(), $month, $year);
    }

    /**
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    private function scopePaid(Builder $query, int $month, int $year): Builder
    {
        $start = CarbonImmutable::create($year, $month, 1);

        return $query
            ->where('payment_status', PaymentStatusEnum::Paid)
            ->whereBetween('created_at', [$start, $start->endOfMonth()]);
    }
}
