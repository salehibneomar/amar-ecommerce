<?php

namespace App\Services\Backoffice\People;

use App\Enums\Order\PaymentStatusEnum;
use App\Enums\User\StaffCompensationTypeEnum;
use App\Models\Order;
use App\Models\StaffPayment;
use App\Traits\Query\TableQueryTrait;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class StaffPaymentService
{
    use TableQueryTrait;

    public function __construct(private readonly StaffService $staffService) {}

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'period_year', 'amount', 'paid_at'];
    }

    /**
     * @return LengthAwarePaginator<int, StaffPayment>
     */
    public function getPayments(int $staffId, string $search = '', int $perPage = 10, string $sortBy = 'period_year', string $sortDirection = 'desc'): LengthAwarePaginator
    {
        return $this->paginateTableQuery(
            query: StaffPayment::query()->with('paidBy')->where('user_id', $staffId),
            search: $search,
            searchColumns: ['notes'],
            filters: [],
            perPage: $perPage,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            sortableColumns: $this->sortableColumns(),
            defaultSortBy: 'period_year',
        );
    }

    public function monthlySales(int $month, int $year): int
    {
        $start = CarbonImmutable::create($year, $month, 1);

        return (int) Order::query()
            ->where('payment_status', PaymentStatusEnum::Paid)
            ->whereBetween('created_at', [$start, $start->endOfMonth()])
            ->sum('total');
    }

    /**
     * @return array{type: StaffCompensationTypeEnum, value: int, sales: int|null, amount: int}
     */
    public function calculate(int $staffId, int $month, int $year): array
    {
        $profile = $this->staffService->getStaffById($staffId)->staffProfile;

        if ($profile === null) {
            throw ValidationException::withMessages(['staffId' => __('Set the compensation for this staff member first.')]);
        }

        $value = $profile->compensation_value;

        if ($profile->compensation_type === StaffCompensationTypeEnum::Salary) {
            return ['type' => $profile->compensation_type, 'value' => $value, 'sales' => null, 'amount' => $value];
        }

        $sales = $this->monthlySales($month, $year);

        return ['type' => $profile->compensation_type, 'value' => $value, 'sales' => $sales, 'amount' => (int) round($sales * $value / 100)];
    }

    public function record(int $staffId, int $month, int $year, int $amount, ?string $notes, int $paidById): StaffPayment
    {
        $exists = StaffPayment::query()
            ->where('user_id', $staffId)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['periodMonth' => __('A payment for this month is already recorded.')]);
        }

        $calculation = $this->calculate($staffId, $month, $year);

        return StaffPayment::create([
            'user_id' => $staffId,
            'amount' => $amount,
            'period_month' => $month,
            'period_year' => $year,
            'total_sales_snapshot' => $calculation['sales'],
            'compensation_type_snapshot' => $calculation['type'],
            'compensation_value_snapshot' => $calculation['value'],
            'notes' => filled($notes) ? $notes : null,
            'paid_at' => now(),
            'paid_by' => $paidById,
        ]);
    }

    public function getPaymentById(int $staffId, int|string $id): StaffPayment
    {
        return StaffPayment::query()->with(['user', 'paidBy'])->where('user_id', $staffId)->findOrFail($id);
    }

    public function delete(int $staffId, int|string $id): bool
    {
        return (bool) StaffPayment::query()->where('user_id', $staffId)->findOrFail($id)->delete();
    }
}
