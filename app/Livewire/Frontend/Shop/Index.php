<?php

namespace App\Livewire\Frontend\Shop;

use App\Models\Product;
use App\Services\Frontend\Shop\PageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read array<string, string> $sortOptions
 */
class Index extends Component
{
    use WithPagination;

    private const CATEGORY_PAGE_SIZE = 5;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'category', except: '')]
    public string $categoryFilter = '';

    /** @var list<int> */
    public array $categoryIds = [];

    #[Session]
    public int $categoryLimit = self::CATEGORY_PAGE_SIZE;

    #[Url(except: 'latest')]
    public string $sort = 'latest';

    #[Url(as: 'min', except: '')]
    public string $minPrice = '';

    #[Url(as: 'max', except: '')]
    public string $maxPrice = '';

    protected PageService $pageService;

    public function mount(): void
    {
        $this->categoryIds = $this->pageService->linkedCategories($this->filterCategoryIds());
        $this->categoryFilter = implode(',', $this->categoryIds);
    }

    public function boot(PageService $pageService): void
    {
        $this->pageService = $pageService;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSort(string $sort): void
    {
        $this->sort = array_key_exists($sort, $this->sortOptions) ? $sort : 'latest';
        $this->resetPage();
    }

    public function updatedMinPrice(): void
    {
        $this->resetPage();
    }

    public function updatedMaxPrice(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryIds(): void
    {
        $this->categoryIds = $this->pageService->selectCategories($this->filterCategoryIds(), array_map('intval', $this->categoryIds));
        $this->categoryFilter = implode(',', $this->categoryIds);
        $this->resetPage();
    }

    public function loadMoreCategories(): void
    {
        $this->categoryLimit += self::CATEGORY_PAGE_SIZE;
        unset($this->categories);
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryFilter', 'categoryIds', 'sort', 'minPrice', 'maxPrice']);
        $this->resetPage();
    }

    #[Computed]
    public function hasFilters(): bool
    {
        return trim($this->search) !== '' || $this->categoryIds !== [] || $this->sort !== 'latest' || $this->minPrice !== '' || $this->maxPrice !== '';
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return $this->pageService->searchProducts($this->search, array_map('intval', $this->categoryIds), $this->sort, $this->priceBound($this->minPrice), $this->priceBound($this->maxPrice));
    }

    /**
     * @return array{items: list<array{id: int, name: string, is_featured: bool, children: list<array<string, mixed>>}>, has_more: bool}
     */
    #[Computed]
    public function categories(): array
    {
        return $this->pageService->getCategoryTreeSlice($this->categoryLimit);
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function sortOptions(): array
    {
        return [
            'latest' => __('Newest first'),
            'price_asc' => __('Price: low to high'),
            'price_desc' => __('Price: high to low'),
        ];
    }

    /**
     * @return list<int>
     */
    private function filterCategoryIds(): array
    {
        return array_values(array_unique(array_filter(array_map('intval', explode(',', trim($this->categoryFilter, '[]'))))));
    }

    private function priceBound(string $value): ?int
    {
        return ctype_digit(trim($value)) ? (int) trim($value) : null;
    }

    public function render(): View
    {
        return view('components.frontend.shop.index');
    }
}
