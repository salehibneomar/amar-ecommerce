<?php

namespace App\Livewire\Backoffice\Faqs;

use App\Enums\Content\FaqStatusEnum;
use App\Livewire\Concerns\InteractsWithTable;
use App\Models\Faq;
use App\Services\Backoffice\Content\FaqService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use InteractsWithTable, WithPagination;

    public string $status = '';

    public bool $showForm = false;

    public bool $showDeleteConfirmation = false;

    public ?int $editingFaqId = null;

    public bool $showDetails = false;

    /** @var array<string, string|bool|null> */
    public array $details = [];

    public ?int $deletingFaqId = null;

    public string $title = '';

    public string $description = '';

    public string $faqStatus = 'draft';

    protected FaqService $faqService;

    public function boot(FaqService $faqService): void
    {
        $this->faqService = $faqService;
    }

    protected function sortableColumns(): array
    {
        return $this->faqService->sortableColumns();
    }

    public function updatedStatus(string $status): void
    {
        $this->status = FaqStatusEnum::tryFrom($status)->value ?? '';
        $this->resetTableFilters();
    }

    public function updatedShowForm(bool $showForm): void
    {
        if (! $showForm) {
            $this->resetForm();
        }
    }

    public function createFaq(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function viewFaq(int $faqId): void
    {
        $faq = $this->faqService->getFaqById($faqId);

        $this->details = [
            __('Title') => $faq->title,
            __('Status') => $faq->status->label(),
            __('Created') => formatDateTime($faq->created_at),
            __('Last Updated by') => $faq->lastUpdateLabel(),
            __('Description') => $faq->description,
        ];
        $this->showDetails = true;
    }

    public function editFaq(int $faqId): void
    {
        $faq = $this->faqService->getFaqById($faqId);

        $this->editingFaqId = $faq->id;
        $this->title = $faq->title;
        $this->description = $faq->description;
        $this->faqStatus = $faq->status->value;
        $this->showForm = true;
    }

    public function saveFaq(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'faqStatus' => ['required', Rule::enum(FaqStatusEnum::class)],
        ]);

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => FaqStatusEnum::from($validated['faqStatus']),
        ];

        if ($this->editingFaqId) {
            $this->faqService->update($this->editingFaqId, $data);
            $message = __('FAQ updated successfully.');
        } else {
            $this->faqService->create($data);
            $message = __('FAQ created successfully.');
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->faqs);
        $this->dispatch('toast-show', slots: ['text' => $message], dataset: ['variant' => 'success']);
    }

    public function confirmDeleteFaq(int $faqId): void
    {
        $this->deletingFaqId = $faqId;
        $this->showDeleteConfirmation = true;
    }

    public function deleteFaq(): void
    {
        if (! $this->deletingFaqId) {
            throw ValidationException::withMessages([
                'deletingFaqId' => __('Select a FAQ before deleting it.'),
            ]);
        }

        $this->faqService->delete($this->deletingFaqId);
        $this->showDeleteConfirmation = false;
        $this->deletingFaqId = null;
        unset($this->faqs);
        $this->dispatch('toast-show', slots: ['text' => __('FAQ deleted successfully.')], dataset: ['variant' => 'success']);
    }

    /**
     * @return LengthAwarePaginator<int, Faq>
     */
    #[Computed]
    public function faqs(): LengthAwarePaginator
    {
        return $this->faqService->getFaqs(
            search: $this->search,
            perPage: $this->perPage,
            status: FaqStatusEnum::tryFrom($this->status),
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
        );
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return collect(FaqStatusEnum::cases())
            ->mapWithKeys(fn (FaqStatusEnum $status): array => [$status->value => $status->label()])
            ->all();
    }

    public function render(): View
    {
        return view('components.backoffice.faqs.index');
    }

    private function resetForm(): void
    {
        $this->reset(['editingFaqId', 'title', 'description']);
        $this->faqStatus = FaqStatusEnum::Draft->value;
        $this->resetValidation();
    }
}
