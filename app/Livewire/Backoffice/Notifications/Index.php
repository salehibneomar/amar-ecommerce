<?php

namespace App\Livewire\Backoffice\Notifications;

use App\Enums\Notification\NotificationReadStatusEnum;
use App\Livewire\Concerns\InteractsWithNotifications;
use App\Livewire\Concerns\InteractsWithTable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use InteractsWithNotifications, InteractsWithTable, WithPagination;

    public string $status = '';

    protected function sortableColumns(): array
    {
        return [];
    }

    public function updatedStatus(string $status): void
    {
        $this->status = NotificationReadStatusEnum::tryFrom($status)->value ?? '';
        $this->resetTableFilters();
    }

    /**
     * @return LengthAwarePaginator<int, array{id: string, label: string, message: string, status: NotificationReadStatusEnum, time: string, date: string}>
     */
    #[Computed]
    public function notifications(): LengthAwarePaginator
    {
        return $this->inboxService->paginate(
            userId: (int) Auth::id(),
            search: $this->search,
            status: NotificationReadStatusEnum::tryFrom($this->status),
            perPage: $this->perPage,
        );
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return collect(NotificationReadStatusEnum::cases())
            ->mapWithKeys(fn (NotificationReadStatusEnum $status): array => [$status->value => $status->label()])
            ->all();
    }

    public function render(): View
    {
        return view('components.backoffice.notifications.index');
    }
}
