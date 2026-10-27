<?php

namespace App\Livewire\Backoffice\Settings;

use App\Enums\System\ThemeColorEnum;
use App\Enums\User\PermissionEnum;
use App\Services\Backoffice\System\SystemSettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Appearance extends Component
{
    public string $themeColor = '';

    protected SystemSettingService $systemSettingService;

    public function boot(SystemSettingService $systemSettingService): void
    {
        $this->systemSettingService = $systemSettingService;
    }

    public function mount(): void
    {
        $this->themeColor = $this->systemSettingService->themeColor()->value;
    }

    public function saveTheme(): void
    {
        $this->authorize(PermissionEnum::ManageTheme->value);

        $this->validate(['themeColor' => ['required', Rule::enum(ThemeColorEnum::class)]]);

        $this->systemSettingService->saveThemeColor(ThemeColorEnum::from($this->themeColor));

        session()->flash('success', __('Theme saved.'));

        $this->redirectRoute('appearance.edit');
    }

    /**
     * @return array<string, array{label: string, theme: array{palette: array<int, string>, brand: string, brand_hover: string, brand_dark: string, foreground: string}}>
     */
    #[Computed]
    public function themeOptions(): array
    {
        return $this->systemSettingService->themeOptions();
    }

    public function render(): View
    {
        return view('components.backoffice.settings.appearance');
    }
}
