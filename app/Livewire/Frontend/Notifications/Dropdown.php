<?php

namespace App\Livewire\Frontend\Notifications;

use App\Enums\Notification\NotificationReadStatusEnum;
use App\Livewire\Concerns\InteractsWithNotifications;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dropdown extends Component
{
    use InteractsWithNotifications;

    /**
     * @return list<array{id: string, label: string, message: string, status: NotificationReadStatusEnum, time: string, date: string}>
     */
    #[Computed]
    public function notifications(): array
    {
        return $this->inboxService->latest((int) Auth::id());
    }

    public function render(): View
    {
        return view('components.frontend.notifications.dropdown');
    }
}
