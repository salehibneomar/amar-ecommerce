<?php

namespace App\Livewire\Frontend\Search;

use App\Models\Book;
use App\Services\Frontend\Shop\PageService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * @property-read bool $isSearching
 * @property-read Collection<int, Book> $results
 */
class Index extends Component
{
    private const MIN_LENGTH = 2;

    private const LIMIT = 5;

    public string $query = '';

    protected PageService $pageService;

    public function boot(PageService $pageService): void
    {
        $this->pageService = $pageService;
    }

    #[Computed]
    public function isSearching(): bool
    {
        return mb_strlen(trim($this->query)) >= self::MIN_LENGTH;
    }

    /**
     * @return Collection<int, Book>
     */
    #[Computed]
    public function results(): Collection
    {
        return $this->isSearching ? $this->pageService->searchProductsByTitle($this->query, self::LIMIT + 1) : new Collection;
    }

    #[Computed]
    public function hasMore(): bool
    {
        return $this->results->count() > self::LIMIT;
    }

    /**
     * @return Collection<int, Book>
     */
    #[Computed]
    public function visibleResults(): Collection
    {
        return $this->results->take(self::LIMIT);
    }

    public function render(): View
    {
        return view('components.frontend.search.index');
    }
}
