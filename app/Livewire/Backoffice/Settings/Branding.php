<?php

namespace App\Livewire\Backoffice\Settings;

use App\Services\Backoffice\System\SystemSettingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Branding extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $logoUpload = null;

    public ?TemporaryUploadedFile $iconUpload = null;

    public ?TemporaryUploadedFile $faviconUpload = null;

    public bool $removeLogo = false;

    public bool $removeIcon = false;

    public bool $removeFavicon = false;

    protected SystemSettingService $systemSettingService;

    public function boot(SystemSettingService $systemSettingService): void
    {
        $this->systemSettingService = $systemSettingService;
    }

    public function saveBrand(): void
    {
        $this->validate([
            'logoUpload' => mediaValidationRules('system.media.logo'),
            'iconUpload' => mediaValidationRules('system.media.icon'),
            'faviconUpload' => mediaValidationRules('system.media.favicon'),
        ]);

        $this->systemSettingService->updateBrandMedia(
            ['logo' => $this->logoUpload, 'icon' => $this->iconUpload, 'favicon' => $this->faviconUpload],
            ['logo' => $this->removeLogo, 'icon' => $this->removeIcon, 'favicon' => $this->removeFavicon],
        );

        $this->reset(['logoUpload', 'iconUpload', 'faviconUpload', 'removeLogo', 'removeIcon', 'removeFavicon']);
        unset($this->brandFields);

        $this->dispatch('toast-show', slots: ['text' => __('Branding saved.')], dataset: ['variant' => 'success']);
    }

    /**
     * @return array<string, array{image: bool, accept: string, hint: string, media: Media|null, previewUrl: string|null}>
     */
    #[Computed]
    public function brandFields(): array
    {
        return $this->systemSettingService->brandFields();
    }

    public function render(): View
    {
        return view('components.backoffice.settings.branding');
    }
}
