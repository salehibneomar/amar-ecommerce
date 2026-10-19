<?php

namespace App\Livewire\Concerns;

use App\Services\Auth\InboxService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

trait InteractsWithNotifications
{
    protected InboxService $inboxService;

    public function bootInteractsWithNotifications(InboxService $inboxService): void
    {
        $this->inboxService = $inboxService;
    }

    #[Computed]
    public function unreadCount(): int
    {
        return $this->inboxService->countUnread((int) Auth::id());
    }

    public function openNotification(string $id): void
    {
        $this->redirect($this->inboxService->open((int) Auth::id(), $id), navigate: true);
    }

    public function toggleNotification(string $id): void
    {
        $this->inboxService->toggleRead((int) Auth::id(), $id);
        unset($this->notifications, $this->unreadCount);
    }

    public function markAllNotifications(bool $read): void
    {
        $this->inboxService->markAll((int) Auth::id(), $read);
        unset($this->notifications, $this->unreadCount);
    }
}
