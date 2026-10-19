<?php

namespace App\Livewire\Backoffice\Staff;

use App\Enums\User\PermissionEnum;
use App\Enums\User\StaffCompensationTypeEnum;
use App\Enums\User\UserRoleEnum;
use App\Livewire\Concerns\InteractsWithTable;
use App\Models\User;
use App\Services\Backoffice\People\RoleService;
use App\Services\Backoffice\People\StaffService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read array<string, string> $roleOptions
 */
class Index extends Component
{
    use InteractsWithTable, WithPagination;

    public bool $showForm = false;

    public bool $showStatusConfirmation = false;

    public bool $showPermissions = false;

    public bool $showDetails = false;

    /** @var array<string, string|bool|null> */
    public array $details = [];

    public ?int $editingStaffId = null;

    public ?int $statusStaffId = null;

    public ?int $permissionsStaffId = null;

    public bool $statusStaffIsActive = false;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    public string $compensationType = 'salary';

    public string $compensationValue = '';

    public string $joinedAt = '';

    public string $leftAt = '';

    /** @var list<string> */
    public array $permissions = [];

    /** @var list<string> */
    public array $rolePermissions = [];

    public bool $permissionsForAdmin = false;

    protected StaffService $staffService;

    protected RoleService $roleService;

    public function boot(StaffService $staffService, RoleService $roleService): void
    {
        $this->staffService = $staffService;
        $this->roleService = $roleService;
    }

    protected function sortableColumns(): array
    {
        return $this->staffService->sortableColumns();
    }

    public function updatedShowForm(bool $showForm): void
    {
        if (! $showForm) {
            $this->resetForm();
        }
    }

    public function createStaff(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function viewStaff(int $staffId): void
    {
        $member = $this->staffService->getStaffById($staffId);
        $profile = $member->staffProfile;

        $this->details = [
            __('Name') => $member->name,
            __('Email') => $member->email,
            __('Role') => $member->roles->pluck('label')->implode(', '),
            __('Status') => $member->status->label(),
            __('Joined') => $profile?->joined_at?->format('d/m/Y'),
            __('Left') => $profile?->left_at?->format('d/m/Y'),
            __('Compensation') => $profile?->compensationLabel(),
            __('Compensation Type') => $profile?->compensation_type->label(),
            __('Two-Factor Login') => $member->two_factor_confirmed_at !== null,
            __('Added') => formatDateTime($member->created_at),
            __('Extra Permissions') => $member->permissions->pluck('name')->map(fn (string $name): string => $this->permissionOptions[$name] ?? $name)->implode(', '),
        ];
        $this->showDetails = true;
    }

    public function editStaff(int $staffId): void
    {
        $member = $this->staffService->getStaffById($staffId);

        $this->editingStaffId = $member->id;
        $this->name = $member->name;
        $this->email = $member->email;
        $this->password = '';
        $this->role = (string) $member->getRoleNames()->first();
        $this->compensationType = $member->staffProfile?->compensation_type->value ?? StaffCompensationTypeEnum::Salary->value;
        $this->compensationValue = $member->staffProfile ? (string) $member->staffProfile->compensation_value : '';
        $this->joinedAt = $member->staffProfile?->joined_at?->format('Y-m-d') ?? '';
        $this->leftAt = $member->staffProfile?->left_at?->format('Y-m-d') ?? '';
        $this->showForm = true;
    }

    public function saveStaff(): void
    {
        $isPercentage = $this->compensationType === StaffCompensationTypeEnum::Percentage->value;

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingStaffId)],
            'password' => [$this->editingStaffId ? 'nullable' : 'required', 'string', Password::default()],
            'role' => ['required', Rule::in(array_keys($this->roleOptions))],
            'compensationType' => ['required', Rule::enum(StaffCompensationTypeEnum::class)],
            'compensationValue' => ['required', 'integer', 'min:0', $isPercentage ? 'max:100' : 'max:9999999'],
            'joinedAt' => ['nullable', 'date'],
            'leftAt' => ['nullable', 'date', 'after_or_equal:joinedAt'],
        ]);

        $this->staffService->save($this->editingStaffId, [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ?: null,
            'role' => $validated['role'],
            'compensation_type' => $validated['compensationType'],
            'compensation_value' => $validated['compensationValue'],
            'joined_at' => $validated['joinedAt'],
            'left_at' => $validated['leftAt'],
        ]);

        $message = $this->editingStaffId ? __('Staff member updated successfully.') : __('Staff member added successfully.');

        $this->showForm = false;
        $this->resetForm();
        unset($this->staff);
        $this->dispatch('toast-show', slots: ['text' => $message], dataset: ['variant' => 'success']);
    }

    public function updatedShowPermissions(bool $showPermissions): void
    {
        if (! $showPermissions) {
            $this->reset(['permissionsStaffId', 'permissions', 'rolePermissions', 'permissionsForAdmin']);
            $this->resetValidation();
        }
    }

    public function editPermissions(int $staffId): void
    {
        $form = $this->staffService->getPermissionForm($staffId);

        $this->permissionsStaffId = $staffId;
        $this->rolePermissions = $form['role'];
        $this->permissionsForAdmin = $form['is_admin'];
        $this->permissions = array_values(array_unique([...$form['role'], ...$form['direct']]));
        $this->resetValidation();
        $this->showPermissions = true;
    }

    public function savePermissions(): void
    {
        $validated = $this->validate([
            'permissions' => ['array'],
            'permissions.*' => [Rule::enum(PermissionEnum::class)],
        ]);

        if ($this->permissionsStaffId) {
            $this->staffService->syncPermissions($this->permissionsStaffId, array_values(array_diff($validated['permissions'], $this->rolePermissions)));
        }

        $this->showPermissions = false;
        $this->updatedShowPermissions(false);
        unset($this->staff);
        $this->dispatch('toast-show', slots: ['text' => __('Permissions updated.')], dataset: ['variant' => 'success']);
    }

    public function confirmToggleStatus(int $staffId): void
    {
        $this->statusStaffId = $staffId;
        $this->statusStaffIsActive = $this->staffService->getStaffById($staffId)->status->isActive();
        $this->resetValidation();
        $this->showStatusConfirmation = true;
    }

    public function toggleStatus(): void
    {
        if (! $this->statusStaffId) {
            return;
        }

        $member = $this->staffService->toggleStatus($this->statusStaffId);
        $message = $member->status->isActive() ? __('Staff member activated.') : __('Staff member deactivated.');

        $this->showStatusConfirmation = false;
        $this->statusStaffId = null;
        unset($this->staff);
        $this->dispatch('toast-show', slots: ['text' => $message], dataset: ['variant' => 'success']);
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function staff(): LengthAwarePaginator
    {
        return $this->staffService->getStaff(
            exceptId: (int) Auth::id(),
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
    public function roleOptions(): array
    {
        $user = Auth::user();

        return $this->roleService->getRoleOptions(includeAdmin: $user instanceof User && $user->isSuperAdmin());
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function permissionOptions(): array
    {
        return PermissionEnum::options();
    }

    /**
     * @return array<string, array<string, string>>
     */
    #[Computed]
    public function permissionGroups(): array
    {
        return PermissionEnum::groupedOptions(toAdmin: $this->permissionsForAdmin);
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function compensationOptions(): array
    {
        return collect(StaffCompensationTypeEnum::cases())
            ->mapWithKeys(fn (StaffCompensationTypeEnum $type): array => [$type->value => $type->label()])
            ->all();
    }

    #[Computed]
    public function isAdminRole(): bool
    {
        return $this->role === UserRoleEnum::Admin->value;
    }

    #[Computed]
    public function isPercentage(): bool
    {
        return $this->compensationType === StaffCompensationTypeEnum::Percentage->value;
    }

    public function render(): View
    {
        return view('components.backoffice.staff.index');
    }

    private function resetForm(): void
    {
        $this->reset(['editingStaffId', 'name', 'email', 'password', 'compensationValue', 'leftAt']);
        $this->joinedAt = now()->format('Y-m-d');
        $this->role = UserRoleEnum::Moderator->value;
        $this->compensationType = StaffCompensationTypeEnum::Salary->value;
        $this->resetValidation();
    }
}
