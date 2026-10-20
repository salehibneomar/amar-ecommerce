<?php

use App\Enums\System\MaintenanceStatusEnum;
use App\Services\Backoffice\System\SystemService;
use App\Services\Backoffice\System\SystemSettingService;
use Illuminate\Support\Facades\Date;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

new #[Title('System')] class extends Component {
    use WithFileUploads;

    public ?TemporaryUploadedFile $logoUpload = null;

    public ?TemporaryUploadedFile $iconUpload = null;

    public ?TemporaryUploadedFile $faviconUpload = null;

    public bool $removeLogo = false;

    public bool $removeIcon = false;

    public bool $removeFavicon = false;

    public bool $maintenanceEnabled = false;

    public string $maintenanceStartsAt = '';

    public string $maintenanceEndsAt = '';

    protected SystemService $systemService;

    protected SystemSettingService $systemSettingService;

    public function boot(SystemService $systemService, SystemSettingService $systemSettingService): void
    {
        $this->systemService = $systemService;
        $this->systemSettingService = $systemSettingService;
    }

    public function mount(): void
    {
        $this->maintenanceEnabled = $this->systemSettingService->isMaintenanceEnabled();
        $this->maintenanceStartsAt = (string) $this->systemSettingService->maintenanceStartsAt()?->format('Y-m-d\TH:i');
        $this->maintenanceEndsAt = (string) $this->systemSettingService->maintenanceEndsAt()?->format('Y-m-d\TH:i');
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

    public function saveMaintenance(): void
    {
        if (! $this->maintenanceEnabled) {
            $this->reset(['maintenanceStartsAt', 'maintenanceEndsAt']);
        }

        $this->validate([
            'maintenanceStartsAt' => ['nullable', 'date'],
            'maintenanceEndsAt' => ['nullable', 'date', 'after:now', ...(filled($this->maintenanceStartsAt) ? ['after:maintenanceStartsAt'] : [])],
        ]);

        $this->systemSettingService->saveMaintenance(
            $this->maintenanceEnabled,
            filled($this->maintenanceStartsAt) ? Date::parse($this->maintenanceStartsAt) : null,
            filled($this->maintenanceEndsAt) ? Date::parse($this->maintenanceEndsAt) : null,
        );

        unset($this->maintenanceStatus, $this->bypassUrl);

        $this->dispatch('toast-show', slots: ['text' => __('Maintenance settings saved.')], dataset: ['variant' => 'success']);
    }

    public function clearCache(): void
    {
        $this->systemService->clearCache();

        session()->flash('success', __('Application cache cleared.'));

        $this->skipRender();
        $this->redirectRoute('system.edit');
    }

    public function optimize(): void
    {
        $this->systemService->optimize();

        session()->flash('success', __('Application optimized.'));

        $this->skipRender();
        $this->redirectRoute('system.edit');
    }

    /**
     * @return array<string, array{image: bool, accept: string, hint: string, media: Media|null, previewUrl: string|null}>
     */
    #[Computed]
    public function brandFields(): array
    {
        return $this->systemSettingService->brandFields();
    }

    #[Computed]
    public function maintenanceStatus(): MaintenanceStatusEnum
    {
        return $this->systemSettingService->maintenanceStatus();
    }

    #[Computed]
    public function bypassUrl(): ?string
    {
        $secret = $this->systemSettingService->maintenanceSecret();

        return $secret ? url($secret) : null;
    }
}; ?>

<section class="w-full">
    <x-backoffice.layout.main-content-container :title="__('System')" :subtitle="__('Branding, maintenance mode and application cache')" :breadcrumbs="[['label' => __('Settings'), 'href' => route('settings.index')], ['label' => __('System')]]">
        <flux:card class="space-y-8">
            <form wire:submit="saveBrand" class="space-y-5">
                <div>
                    <flux:heading>{{ __('Branding') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('Replace the logo, icon and favicon. A removed image falls back to the default.') }}</flux:text>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-media.upload :label="__('Logo')" :field="$this->brandFields['logo']" upload="logoUpload" remove="removeLogo" :selected="$logoUpload" :marked-for-removal="$removeLogo" />
                    <x-media.upload :label="__('Icon (Small Square)')" :field="$this->brandFields['icon']" upload="iconUpload" remove="removeIcon" :selected="$iconUpload" :marked-for-removal="$removeIcon" />
                    <x-media.upload :label="__('Favicon')" :field="$this->brandFields['favicon']" upload="faviconUpload" remove="removeFavicon" :selected="$faviconUpload" :marked-for-removal="$removeFavicon" />
                </div>

                <x-form.actions>
                    <x-ui.button type="submit" variant="primary" data-test="save-brand-button">{{ __('Save') }}</x-ui.button>
                </x-form.actions>
            </form>

            <flux:separator />

            <div class="grid grid-cols-1 divide-y divide-line lg:grid-cols-2 lg:divide-x lg:divide-y-0">
                <form wire:submit="saveMaintenance" class="space-y-5 pb-8 lg:pe-8 lg:pb-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <flux:heading>{{ __('Maintenance mode') }}</flux:heading>
                            <x-ui.badge :color="$this->maintenanceStatus->color()" data-test="maintenance-status">{{ $this->maintenanceStatus->label() }}</x-ui.badge>
                        </div>
                        <flux:text class="mt-1">{{ __('Every public and customer page shows a friendly maintenance page during the window, while the admin panel stays open for admins. Leave the times empty to start now and end manually.') }}</flux:text>
                    </div>

                    <x-backoffice.form.toggle-field wire:model.live="maintenanceEnabled" :label="__('Enable Maintenance Mode')" />

                    @if ($maintenanceEnabled)
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <flux:input wire:model="maintenanceStartsAt" type="datetime-local" :label="__('Starts at')" :description="__('Empty starts now')" />
                            <flux:input wire:model="maintenanceEndsAt" type="datetime-local" :label="__('Ends at')" :description="__('Empty means until turned off')" />
                        </div>
                    @endif

                    @if ($this->bypassUrl)
                        <flux:input :value="$this->bypassUrl" readonly copyable :label="__('Bypass Link')" :description="__('Opens the public site in this browser while maintenance is active.')" data-test="bypass-link" />
                    @endif

                    <x-form.actions>
                        <x-ui.button type="submit" variant="primary" data-test="save-maintenance-button">{{ __('Save') }}</x-ui.button>
                    </x-form.actions>
                </form>

                <div class="space-y-5 pt-8 lg:ps-8 lg:pt-0">
                    <div>
                        <flux:heading>{{ __('Application cache') }}</flux:heading>
                        <flux:text class="mt-1">{{ __('Clear cached config, routes and views, or rebuild them for faster responses') }}</flux:text>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <x-ui.button wire:click="clearCache" wire:loading.attr="disabled" data-test="clear-cache-button">
                            {{ __('Clear Cache') }}
                        </x-ui.button>
                        <x-ui.button variant="primary" wire:click="optimize" wire:loading.attr="disabled" data-test="optimize-button">
                            {{ __('Optimize') }}
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </flux:card>
    </x-backoffice.layout.main-content-container>
</section>
