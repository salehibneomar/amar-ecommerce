<?php

namespace App\Livewire\Frontend\Notifications;

use App\Enums\Notification\NotificationReadStatusEnum;
use App\Livewire\Concerns\InteractsWithNotifications;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use InteractsWithNotifications, WithPagination;

    /**
     * @return LengthAwarePaginator<int, array{id: string, label: string, message: string, status: NotificationReadStatusEnum, time: string, date: string}>
     */
    #[Computed]
    public function notifications(): LengthAwarePaginator
    {
        return $this->inboxService->paginate((int) Auth::id());
    }

    public function render(): View
    {
        return view('components.frontend.notifications.index');
    }
}
