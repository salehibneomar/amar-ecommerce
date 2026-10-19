<?php

namespace App\Livewire\Backoffice\Banners;

use App\Enums\Content\BannerStatusEnum;
use App\Livewire\Concerns\InteractsWithTable;
use App\Models\Banner;
use App\Services\Backoffice\Content\BannerService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use InteractsWithTable, WithFileUploads, WithPagination;

    public bool $showForm = false;

    public bool $showDeleteConfirmation = false;

    public ?int $editingBannerId = null;

    public bool $showDetails = false;

    /** @var array<string, string|bool|null> */
    public array $details = [];

    public ?int $deletingBannerId = null;

    public string $status = '';

    public string $title = '';

    public string $subtitle = '';

    public bool $showText = true;

    public string $link = '';

    public string $sortOrder = '1';

    public string $bannerStatus = 'active';

    public string $startsAt = '';

    public string $endsAt = '';

    public ?TemporaryUploadedFile $imageUpload = null;

    protected BannerService $bannerService;

    public function mount(): void
    {
        $this->sortBy = 'sort_order';
        $this->sortDirection = 'asc';
    }

    public function boot(BannerService $bannerService): void
    {
        $this->bannerService = $bannerService;
    }

    protected function sortableColumns(): array
    {
        return $this->bannerService->sortableColumns();
    }

    public function updatedShowForm(bool $showForm): void
    {
        if (! $showForm) {
            $this->resetForm();
        }
    }

    public function createBanner(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function viewBanner(int $bannerId): void
    {
        $banner = $this->bannerService->getBannerById($bannerId);

        $this->details = [
            __('Title') => $banner->title,
            __('Subtitle') => $banner->subtitle,
            __('Link') => $banner->link,
            __('Display order') => (string) $banner->sort_order,
            __('Status') => $banner->status->label(),
            __('Show text') => $banner->show_text,
            __('Show from') => formatDateTime($banner->starts_at),
            __('Show until') => formatDateTime($banner->ends_at),
            __('Last updated by') => $banner->lastUpdateLabel(),
        ];
        $this->showDetails = true;
    }

    public function editBanner(int $bannerId): void
    {
        $banner = $this->bannerService->getBannerById($bannerId);

        $this->editingBannerId = $banner->id;
        $this->title = $banner->title;
        $this->subtitle = $banner->subtitle ?? '';
        $this->showText = $banner->show_text;
        $this->link = $banner->link ?? '';
        $this->sortOrder = (string) $banner->sort_order;
        $this->bannerStatus = $banner->status->value;
        $this->startsAt = $banner->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt = $banner->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->showForm = true;
    }

    public function saveBanner(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'showText' => ['boolean'],
            'link' => ['nullable', 'string', 'max:255', 'regex:/^(\/|https?:\/\/)/'],
            'sortOrder' => ['required', 'integer', 'min:1', 'max:9999'],
            'bannerStatus' => ['required', Rule::enum(BannerStatusEnum::class)],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
            'imageUpload' => mediaValidationRules('banner.media.image', required: $this->editingBannerId === null),
        ]);

        $data = [
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'],
            'show_text' => $validated['showText'],
            'link' => $validated['link'],
            'sort_order' => $validated['sortOrder'],
            'status' => BannerStatusEnum::from($validated['bannerStatus']),
            'starts_at' => $validated['startsAt'],
            'ends_at' => $validated['endsAt'],
        ];

        if ($this->editingBannerId) {
            $this->bannerService->update($this->editingBannerId, $data, $this->imageUpload);
            $message = __('Banner updated successfully.');
        } else {
            $this->bannerService->create($data, $this->imageUpload);
            $message = __('Banner created successfully.');
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->banners);
        $this->dispatch('toast-show', slots: ['text' => $message], dataset: ['variant' => 'success']);
    }

    public function confirmDeleteBanner(int $bannerId): void
    {
        $this->deletingBannerId = $bannerId;
        $this->showDeleteConfirmation = true;
    }

    public function deleteBanner(): void
    {
        if (! $this->deletingBannerId) {
            throw ValidationException::withMessages([
                'deletingBannerId' => __('Select a banner before deleting it.'),
            ]);
        }

        $this->bannerService->delete($this->deletingBannerId);
        $this->showDeleteConfirmation = false;
        $this->deletingBannerId = null;
        unset($this->banners);
        $this->dispatch('toast-show', slots: ['text' => __('Banner deleted successfully.')], dataset: ['variant' => 'success']);
    }

    public function updatedStatus(string $status): void
    {
        $this->status = BannerStatusEnum::tryFrom($status)->value ?? '';
        $this->resetTableFilters();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function statusOptions(): array
    {
        return collect(BannerStatusEnum::cases())
            ->mapWithKeys(fn (BannerStatusEnum $status): array => [$status->value => $status->label()])
            ->all();
    }

    /**
     * @return LengthAwarePaginator<int, Banner>
     */
    #[Computed]
    public function banners(): LengthAwarePaginator
    {
        return $this->bannerService->getBanners(
            search: $this->search,
            perPage: $this->perPage,
            status: BannerStatusEnum::tryFrom($this->status),
            sortBy: $this->sortBy,
            sortDirection: $this->sortDirection,
        );
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function imageField(): array
    {
        return $this->editingBannerId
            ? $this->bannerService->getBannerById($this->editingBannerId)->mediaField('banner.media.image', 'regular')
            : ['image' => true, 'accept' => mediaAccept('banner.media.image'), 'hint' => mediaUploadHint('banner.media.image'), 'media' => null, 'previewUrl' => null];
    }

    public function render(): View
    {
        return view('components.backoffice.banners.index');
    }

    private function resetForm(): void
    {
        $this->reset(['editingBannerId', 'title', 'subtitle', 'link', 'startsAt', 'endsAt', 'imageUpload']);
        $this->sortOrder = '1';
        $this->showText = true;
        $this->bannerStatus = BannerStatusEnum::Active->value;
        unset($this->imageField);
        $this->resetValidation();
    }
}
