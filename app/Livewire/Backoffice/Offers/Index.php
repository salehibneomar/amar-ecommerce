<?php

namespace App\Livewire\Backoffice\Offers;

use App\Enums\Content\OfferTypeEnum;
use App\Livewire\Concerns\InteractsWithTable;
use App\Models\Offer;
use App\Services\Backoffice\Catalog\CategoryService;
use App\Services\Backoffice\Content\OfferService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read bool $isSpecific
 * @property-read bool $globalOnly
 * @property-read Offer|null $liveGlobalOverlap
 */
class Index extends Component
{
    use InteractsWithTable, WithPagination;

    public bool $showForm = false;

    public bool $showDeleteConfirmation = false;

    public ?int $editingOfferId = null;

    public bool $showDetails = false;

    /** @var array<string, string|bool|null> */
    public array $details = [];

    public ?int $deletingOfferId = null;

    public string $name = '';

    public string $headline = '';

    public string $type = 'global';

    /** @var list<int> */
    public array $categoryIds = [];

    public string $categorySearch = '';

    public string $description = '';

    public string $discountPercentage = '';

    public bool $isActive = true;

    public string $startsAt = '';

    public string $endsAt = '';

    protected OfferService $offerService;

    protected CategoryService $categoryService;

    public function boot(OfferService $offerService, CategoryService $categoryService): void
    {
        $this->offerService = $offerService;
        $this->categoryService = $categoryService;
    }

    protected function sortableColumns(): array
    {
        return $this->offerService->sortableColumns();
    }

    public function updatedShowForm(bool $showForm): void
    {
        if (! $showForm) {
            $this->resetForm();
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['type', 'startsAt', 'endsAt'], true)) {
            $this->turnOffWhenOverlapping();
        }
    }

    public function createOffer(): void
    {
        $this->resetForm();
        $this->turnOffWhenOverlapping();
        $this->showForm = true;
    }

    public function viewOffer(int $offerId): void
    {
        $offer = $this->offerService->getOfferById($offerId);

        $this->details = [
            __('Name') => $offer->name,
            __('Headline') => $offer->headline,
            ...($this->globalOnly ? [] : [__('Type') => $offer->type->label()]),
            __('Discount') => $offer->discount_percentage.'%',
            __('Active') => $offer->is_active,
            __('Starts') => formatDateTime($offer->starts_at),
            __('Ends') => formatDateTime($offer->ends_at),
            ...($offer->isGlobal() ? [] : [__('Categories') => $offer->categories->pluck('name')->join(', ')]),
            __('Created') => formatDateTime($offer->created_at),
            __('Last Updated by') => $offer->lastUpdateLabel(),
            __('Description') => $offer->description,
        ];
        $this->showDetails = true;
    }

    public function editOffer(int $offerId): void
    {
        $offer = $this->offerService->getOfferById($offerId);

        $this->editingOfferId = $offer->id;
        $this->name = $offer->name;
        $this->headline = $offer->headline;
        $this->type = $this->globalOnly ? OfferTypeEnum::Global->value : $offer->type->value;
        $this->categoryIds = array_values($offer->categories->modelKeys());
        $this->description = $offer->description ?? '';
        $this->discountPercentage = (string) $offer->discount_percentage;
        $this->isActive = $offer->is_active;
        $this->startsAt = $offer->starts_at?->format('Y-m-d') ?? '';
        $this->endsAt = $offer->ends_at?->format('Y-m-d') ?? '';
        $this->turnOffWhenOverlapping();
        $this->showForm = true;
    }

    public function saveOffer(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'headline' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(OfferTypeEnum::class)],
            'categoryIds' => [Rule::requiredIf($this->isSpecific), 'array'],
            'categoryIds.*' => ['integer', Rule::exists('categories', 'id')->whereNull('parent_id')],
            'description' => ['nullable', 'string', 'max:1000'],
            'discountPercentage' => ['required', 'integer', 'min:1', 'max:100'],
            'isActive' => ['boolean'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
        ]);

        $data = [
            'name' => $validated['name'],
            'headline' => $validated['headline'],
            'type' => $validated['type'],
            'category_ids' => $validated['categoryIds'] ?? [],
            'description' => $validated['description'],
            'discount_percentage' => $validated['discountPercentage'],
            'is_active' => $validated['isActive'] && ! $this->liveGlobalOverlap,
            'starts_at' => $validated['startsAt'],
            'ends_at' => $validated['endsAt'],
        ];

        if ($this->editingOfferId) {
            $this->offerService->update($this->editingOfferId, $data);
            $message = __('Offer updated successfully.');
        } else {
            $this->offerService->create($data);
            $message = __('Offer created successfully.');
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->offers);
        $this->dispatch('toast-show', slots: ['text' => $message], dataset: ['variant' => 'success']);
    }

    public function confirmDeleteOffer(int $offerId): void
    {
        $this->deletingOfferId = $offerId;
        $this->showDeleteConfirmation = true;
    }

    public function deleteOffer(): void
    {
        if (! $this->deletingOfferId) {
            throw ValidationException::withMessages([
                'deletingOfferId' => __('Select an offer before deleting it.'),
            ]);
        }

        $this->offerService->delete($this->deletingOfferId);
        $this->showDeleteConfirmation = false;
        $this->deletingOfferId = null;
        unset($this->offers);
        $this->dispatch('toast-show', slots: ['text' => __('Offer deleted successfully.')], dataset: ['variant' => 'success']);
    }

    /**
     * @return LengthAwarePaginator<int, Offer>
     */
    #[Computed]
    public function offers(): LengthAwarePaginator
    {
        return $this->offerService->getOffers(
            search: $this->search,
            perPage: $this->perPage,
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
        );
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function typeOptions(): array
    {
        return collect(OfferTypeEnum::cases())
            ->mapWithKeys(fn (OfferTypeEnum $type): array => [$type->value => $type->label()])
            ->all();
    }

    public function addCategory(int $categoryId): void
    {
        if (! in_array($categoryId, $this->categoryIds, true)) {
            $this->categoryIds[] = $categoryId;
        }

        $this->categorySearch = '';
    }

    public function removeCategory(int $categoryId): void
    {
        $this->categoryIds = array_values(array_diff($this->categoryIds, [$categoryId]));
    }

    #[Computed]
    public function isSpecific(): bool
    {
        return $this->type === OfferTypeEnum::Specific->value;
    }

    #[Computed]
    public function globalOnly(): bool
    {
        return isGlobalOfferOnly();
    }

    #[Computed]
    public function liveGlobalOverlap(): ?Offer
    {
        return $this->isSpecific ? null : $this->offerService->findLiveGlobalOverlap($this->editingOfferId, $this->startsAt, $this->endsAt);
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function categoryResults(): array
    {
        return $this->isSpecific && filled($this->categorySearch)
            ? $this->categoryService->searchRootOptions($this->categorySearch, $this->categoryIds)
            : [];
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function selectedCategories(): array
    {
        return $this->categoryService->getOptionsByIds($this->categoryIds);
    }

    public function render(): View
    {
        return view('components.backoffice.offers.index');
    }

    private function resetForm(): void
    {
        $this->reset(['editingOfferId', 'name', 'headline', 'type', 'categoryIds', 'categorySearch', 'description', 'discountPercentage', 'startsAt', 'endsAt']);
        $this->isActive = true;
        $this->resetValidation();
    }

    private function turnOffWhenOverlapping(): void
    {
        unset($this->liveGlobalOverlap);

        if ($this->liveGlobalOverlap) {
            $this->isActive = false;
        }
    }
}
