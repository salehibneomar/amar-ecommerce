<?php

namespace App\Livewire\Backoffice\Users;

use App\Enums\User\UserStatusEnum;
use App\Livewire\Concerns\InteractsWithTable;
use App\Models\User;
use App\Services\Backoffice\People\UserService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use InteractsWithTable, WithPagination;

    public string $status = '';

    public ?int $selectedUserId = null;

    public string $selectedStatus = '';

    public bool $showUserConfirmation = false;

    public bool $showDetails = false;

    /** @var array<string, string|bool|null> */
    public array $details = [];

    protected UserService $userService;

    public function boot(UserService $userService): void
    {
        $this->userService = $userService;
    }

    public function mount(string $search = ''): void
    {
        $this->search = $search;
    }

    protected function sortableColumns(): array
    {
        return $this->userService->sortableColumns();
    }

    public function updatedStatus(string $status): void
    {
        $this->status = UserStatusEnum::tryFrom($status)->value ?? '';

        $this->resetTableFilters();
    }

    public function viewUser(int $userId): void
    {
        $user = $this->userService->getCustomerDetailsById($userId);

        $this->details = [
            __('Name') => $user->name,
            __('Email') => $user->email,
            __('Mobile Number') => $user->phone,
            __('Status') => $user->status->label(),
            __('Email Verified') => formatDateTime($user->email_verified_at),
            __('Orders') => (string) $user->orders_count,
            __('Google Login') => $user->google_id !== null,
            __('Facebook Login') => $user->facebook_id !== null,
            __('Two-Factor Login') => $user->two_factor_confirmed_at !== null,
            __('Registered') => formatDateTime($user->created_at),
            __('Address') => $user->address,
        ];
        $this->showDetails = true;
    }

    public function selectUserAction(int $userId): void
    {
        $user = $this->userService->getUserById($userId);

        $this->selectedStatus = $user->status->toggled()->value;
        $this->selectedUserId = $userId;
        $this->showUserConfirmation = true;
    }

    public function performUserAction(): void
    {
        $targetStatus = UserStatusEnum::tryFrom($this->selectedStatus);

        if (! $this->selectedUserId || ! $targetStatus) {
            throw ValidationException::withMessages([
                'selectedUserId' => __(
                    'Select a user action before continuing.',
                ),
            ]);
        }

        $user = $this->userService->getUserById($this->selectedUserId);

        $this->authorize($targetStatus->transitionAbility(), $user);
        $this->userService->updateStatus($user, $targetStatus);
        $message = $targetStatus->transitionMessage();

        $this->selectedUserId = null;
        $this->selectedStatus = '';
        $this->showUserConfirmation = false;
        unset($this->users);
        $this->dispatch(
            'toast-show',
            slots: ['text' => $message],
            dataset: ['variant' => 'success'],
        );
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return $this->userService->getAllUsers(
            search: $this->search,
            perPage: $this->perPage,
            status: UserStatusEnum::tryFrom($this->status),
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
        return collect(UserStatusEnum::cases())
            ->mapWithKeys(
                fn (UserStatusEnum $status) => [
                    $status->value => $status->label(),
                ],
            )
            ->all();
    }

    public function render(): View
    {
        return view('components.backoffice.users.index');
    }
}
