<?php

namespace App\Livewire\Backoffice\StaffPayments;

use App\Enums\User\StaffCompensationTypeEnum;
use App\Livewire\Concerns\InteractsWithTable;
use App\Models\StaffPayment;
use App\Services\Backoffice\People\StaffPaymentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read array{type: StaffCompensationTypeEnum, value: int, sales: int|null, amount: int}|null $calculation
 */
class Index extends Component
{
    use InteractsWithTable, WithPagination;

    #[Locked]
    public int $staffId;

    #[Locked]
    public string $staffName = '';

    public bool $showForm = false;

    public bool $showDeleteConfirmation = false;

    public bool $showCompensationRequired = false;

    public ?int $deletingPaymentId = null;

    public bool $showDetails = false;

    /** @var array<string, string|bool|null> */
    public array $details = [];

    public int $periodMonth;

    public int $periodYear;

    public string $amount = '';

    public string $notes = '';

    protected StaffPaymentService $staffPaymentService;

    public function mount(int $staffId, string $staffName): void
    {
        $this->staffId = $staffId;
        $this->staffName = $staffName;
        $this->sortBy = 'period_year';
        $this->resetForm();
    }

    public function boot(StaffPaymentService $staffPaymentService): void
    {
        $this->staffPaymentService = $staffPaymentService;
    }

    protected function sortableColumns(): array
    {
        return $this->staffPaymentService->sortableColumns();
    }

    public function updatedShowForm(bool $showForm): void
    {
        if (! $showForm) {
            $this->resetForm();
        }
    }

    public function createPayment(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function updatedPeriodMonth(): void
    {
        $this->fillSuggestedAmount();
    }

    public function updatedPeriodYear(): void
    {
        $this->fillSuggestedAmount();
    }

    public function savePayment(): void
    {
        if (! $this->calculation) {
            $this->showCompensationRequired = true;

            return;
        }

        $validated = $this->validate([
            'periodMonth' => ['required', 'integer', 'between:1,12'],
            'periodYear' => ['required', 'integer', 'between:2020,'.now()->year],
            'amount' => ['required', 'integer', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->staffPaymentService->record(
            $this->staffId,
            (int) $validated['periodMonth'],
            (int) $validated['periodYear'],
            (int) $validated['amount'],
            $validated['notes'],
            (int) Auth::id(),
        );

        $this->showForm = false;
        $this->resetForm();
        $this->resetPage();
        unset($this->payments);
        $this->dispatch('toast-show', slots: ['text' => __('Payment recorded.')], dataset: ['variant' => 'success']);
    }

    public function viewPayment(int $paymentId): void
    {
        $payment = $this->staffPaymentService->getPaymentById($this->staffId, $paymentId);
        $isPercentage = $payment->compensation_type_snapshot->isPercentage();

        $this->details = [
            __('Period') => $payment->periodLabel(),
            __('Amount') => formatMoney($payment->amount),
            __('Compensation Type') => $payment->compensation_type_snapshot->label(),
            __('Rate') => $payment->rateLabel(),
            __('Monthly Sales') => $isPercentage ? formatMoney((int) $payment->total_sales_snapshot) : null,
            __('Paid on') => formatDateTime($payment->paid_at),
            __('Paid by') => $payment->paidBy?->name,
            __('Notes') => $payment->notes,
        ];
        $this->showDetails = true;
    }

    public function confirmDeletePayment(int $paymentId): void
    {
        $this->deletingPaymentId = $paymentId;
        $this->showDeleteConfirmation = true;
    }

    public function deletePayment(): void
    {
        if (! $this->deletingPaymentId) {
            throw ValidationException::withMessages([
                'deletingPaymentId' => __('Select a payment before deleting it.'),
            ]);
        }

        $this->staffPaymentService->delete($this->staffId, $this->deletingPaymentId);
        $this->showDeleteConfirmation = false;
        $this->deletingPaymentId = null;
        unset($this->payments);
        $this->fillSuggestedAmount();
        $this->dispatch('toast-show', slots: ['text' => __('Payment deleted.')], dataset: ['variant' => 'success']);
    }

    /**
     * @return LengthAwarePaginator<int, StaffPayment>
     */
    #[Computed]
    public function payments(): LengthAwarePaginator
    {
        return $this->staffPaymentService->getPayments(
            $this->staffId,
            search: $this->search,
            perPage: $this->perPage,
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
        );
    }

    /**
     * @return array{type: StaffCompensationTypeEnum, value: int, sales: int|null, amount: int}|null
     */
    #[Computed]
    public function calculation(): ?array
    {
        try {
            return $this->staffPaymentService->calculate($this->staffId, $this->periodMonth, $this->periodYear);
        } catch (ValidationException) {
            return null;
        }
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
        return view('components.backoffice.staff-payments.index');
    }

    private function fillSuggestedAmount(): void
    {
        unset($this->calculation);
        $this->amount = $this->calculation ? (string) $this->calculation['amount'] : '';
    }

    private function resetForm(): void
    {
        $previousMonth = now()->startOfMonth()->subMonth();
        $this->periodMonth = (int) $previousMonth->month;
        $this->periodYear = (int) $previousMonth->year;
        $this->notes = '';
        $this->resetValidation();
        $this->fillSuggestedAmount();
    }
}
