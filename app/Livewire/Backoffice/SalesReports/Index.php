<?php

namespace App\Livewire\Backoffice\SalesReports;

use App\Services\Backoffice\Sales\SalesReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url]
    public int $month;

    #[Url]
    public int $year;

    protected SalesReportService $salesReportService;

    public function mount(): void
    {
        $this->month = (int) now()->month;
        $this->year = (int) now()->year;
    }

    public function boot(SalesReportService $salesReportService): void
    {
        $this->salesReportService = $salesReportService;
    }

    public function updatedMonth(mixed $month): void
    {
        $this->month = min(12, max(1, (int) $month));
    }

    public function updatedYear(mixed $year): void
    {
        $this->year = min((int) now()->year, max(2020, (int) $year));
    }

    /**
     * @return array{orders: int, revenue: int, discount: int, items: int, average: int}
     */
    #[Computed]
    public function summary(): array
    {
        return $this->salesReportService->getSummary($this->month, $this->year);
    }

    /**
     * @return list<array{date: string, orders: int, revenue: int}>
     */
    #[Computed]
    public function dailySales(): array
    {
        return $this->salesReportService->getDailySales($this->month, $this->year);
    }

    /**
     * @return list<array{name: string, quantity: int, revenue: int}>
     */
    #[Computed]
    public function topProducts(): array
    {
        return $this->salesReportService->getTopProducts($this->month, $this->year);
    }

    /**
     * @return list<array{label: string, revenue: int, percent: int}>
     */
    #[Computed]
    public function monthlyRevenue(): array
    {
        $months = $this->salesReportService->getMonthlyRevenue($this->year);
        $max = max([0, ...array_column($months, 'revenue')]) ?: 1;

        return array_map(fn (array $month): array => [...$month, 'percent' => (int) round($month['revenue'] / $max * 100)], $months);
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function monthOptions(): array
    {
        return collect(range(1, 12))->mapWithKeys(fn (int $month): array => [$month => now()->startOfYear()->setMonth($month)->format('F')])->all();
    }

    /**
     * @return list<int>
     */
    #[Computed]
    public function yearOptions(): array
    {
        return range((int) now()->year, 2024);
    }

    public function render(): View
    {
        return view('components.backoffice.sales-reports.index');
    }
}
