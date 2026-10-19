<?php

namespace App\Livewire\Backoffice\Roles;

use App\Enums\User\PermissionEnum;
use App\Livewire\Concerns\InteractsWithTable;
use App\Models\Role;
use App\Services\Backoffice\People\RoleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use InteractsWithTable, WithPagination;

    public bool $showForm = false;

    public bool $showDeleteConfirmation = false;

    public ?int $editingRoleId = null;

    public ?int $deletingRoleId = null;

    public string $name = '';

    /** @var list<string> */
    public array $permissions = [];

    protected RoleService $roleService;

    public function boot(RoleService $roleService): void
    {
        $this->roleService = $roleService;
    }

    protected function sortableColumns(): array
    {
        return $this->roleService->sortableColumns();
    }

    public function updatedShowForm(bool $showForm): void
    {
        if (! $showForm) {
            $this->resetForm();
        }
    }

    public function createRole(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editRole(int $roleId): void
    {
        $role = $this->roleService->getRoleForm($roleId);

        $this->editingRoleId = $roleId;
        $this->name = $role['name'];
        $this->permissions = $role['permissions'];
        $this->showForm = true;
    }

    public function saveRole(): void
    {
        $validated = $this->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique('roles', 'label')->ignore($this->editingRoleId),
                Rule::unique('roles', 'name')->ignore($this->editingRoleId),
            ],
            'permissions' => ['array'],
            'permissions.*' => [Rule::enum(PermissionEnum::class)],
        ]);

        $this->roleService->save($this->editingRoleId, $validated['name'], $validated['permissions']);

        $message = $this->editingRoleId ? __('Role updated successfully.') : __('Role created successfully.');

        $this->showForm = false;
        $this->resetForm();
        unset($this->roles);
        $this->dispatch('toast-show', slots: ['text' => $message], dataset: ['variant' => 'success']);
    }

    public function confirmDeleteRole(int $roleId): void
    {
        $this->deletingRoleId = $roleId;
        $this->resetValidation();
        $this->showDeleteConfirmation = true;
    }

    public function deleteRole(): void
    {
        if ($this->deletingRoleId) {
            $this->roleService->delete($this->deletingRoleId);
        }

        $this->showDeleteConfirmation = false;
        $this->deletingRoleId = null;
        unset($this->roles);
        $this->dispatch('toast-show', slots: ['text' => __('Role deleted successfully.')], dataset: ['variant' => 'success']);
    }

    /**
     * @return LengthAwarePaginator<int, Role>
     */
    #[Computed]
    public function roles(): LengthAwarePaginator
    {
        return $this->roleService->getRoles(
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
        return PermissionEnum::groupedOptions();
    }

    public function render(): View
    {
        return view('components.backoffice.roles.index');
    }

    private function resetForm(): void
    {
        $this->reset(['editingRoleId', 'name', 'permissions']);
        $this->resetValidation();
    }
}
