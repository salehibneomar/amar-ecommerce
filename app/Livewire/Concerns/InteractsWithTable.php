<?php

namespace App\Livewire\Concerns;

use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

trait InteractsWithTable
{
    public string $search = '';

    public int $perPage = 10;

    public string $sortBy = 'id';

    public string $sortDirection = 'desc';

    /** @var list<int> */
    protected array $perPageOptions = [5, 10, 20, 50, 100];

    public function updatedSearch(): void
    {
        $this->resetTablePage();
    }

    public function updatedPerPage(int|string|null $perPage): void
    {
        $perPage = (int) $perPage;

        $this->perPage = in_array($perPage, $this->perPageOptions, true)
            ? $perPage
            : 10;

        $this->resetTablePage();
    }

    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortableColumns(), true)) {
            throw ValidationException::withMessages([
                'sortBy' => __('The selected sort column is invalid.'),
            ]);
        }

        if ($this->sortBy === $column) {
            $this->sortDirection =
                $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetTablePage();
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function tableSortableColumns(): array
    {
        return $this->sortableColumns();
    }

    protected function resetTableFilters(): void
    {
        $this->resetTablePage();
    }

    protected function resetTablePage(): void
    {
        $this->resetPage();
    }

    /**
     * @return list<string>
     */
    abstract protected function sortableColumns(): array;
}
