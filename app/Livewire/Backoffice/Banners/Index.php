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

/**
 * @property-read string|null $imageUrl
 */
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

    public bool $showMediaPreview = false;

    public ?int $previewBannerId = null;

    public bool $showImageViewer = false;

    public bool $showMediaUpload = false;

    public ?int $mediaBannerId = null;

    public ?TemporaryUploadedFile $imageUpload = null;

    public bool $removeImage = false;

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
            __('Display Order') => (string) $banner->sort_order,
            __('Status') => $banner->status->label(),
            __('Show Text') => $banner->show_text,
            __('Show From') => formatDateTime($banner->starts_at),
            __('Show Until') => formatDateTime($banner->ends_at),
            __('Created') => formatDateTime($banner->created_at),
            __('Last Updated by') => $banner->lastUpdateLabel(),
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
            $this->bannerService->update($this->editingBannerId, $data);
            $message = __('Banner updated successfully.');
        } else {
            $this->bannerService->create($data);
            $message = __('Banner created successfully.');
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->banners);
        $this->dispatch('toast-show', slots: ['text' => $message], dataset: ['variant' => 'success']);
    }

    public function updatedShowMediaPreview(bool $showMediaPreview): void
    {
        if (! $showMediaPreview) {
            $this->reset(['previewBannerId', 'showImageViewer']);
        }
    }

    public function updatedShowMediaUpload(bool $showMediaUpload): void
    {
        if (! $showMediaUpload) {
            $this->resetMediaUpload();
        }
    }

    public function viewMedia(int $bannerId): void
    {
        $this->previewBannerId = $this->bannerService->getBannerById($bannerId)->id;
        unset($this->previewField, $this->imageUrl);
        $this->showMediaPreview = true;
    }

    public function viewImage(): void
    {
        if (! $this->imageUrl) {
            throw ValidationException::withMessages([
                'imageUpload' => __('This banner does not have an image.'),
            ]);
        }

        $this->showImageViewer = true;
    }

    public function uploadMedia(int $bannerId): void
    {
        $this->resetMediaUpload();
        $this->mediaBannerId = $bannerId;
        unset($this->uploadField);
        $this->showMediaUpload = true;
    }

    public function saveMedia(): void
    {
        if (! $this->mediaBannerId) {
            throw ValidationException::withMessages([
                'mediaBannerId' => __('Select a banner before uploading media.'),
            ]);
        }

        $this->validate([
            'imageUpload' => mediaValidationRules('banner.media.image'),
        ]);

        $this->bannerService->updateImage($this->mediaBannerId, $this->imageUpload, $this->removeImage);

        $this->showMediaUpload = false;
        $this->resetMediaUpload();
        unset($this->banners);
        $this->dispatch('toast-show', slots: ['text' => __('Banner media updated successfully.')], dataset: ['variant' => 'success']);
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
    public function uploadField(): array
    {
        return $this->mediaBannerId ? $this->bannerService->imageField($this->mediaBannerId) : [];
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function previewField(): array
    {
        return $this->previewBannerId ? $this->bannerService->imageField($this->previewBannerId) : [];
    }

    #[Computed]
    public function imageUrl(): ?string
    {
        return $this->previewBannerId ? $this->bannerService->imageUrl($this->previewBannerId, 'regular') : null;
    }

    public function render(): View
    {
        return view('components.backoffice.banners.index');
    }

    private function resetForm(): void
    {
        $this->reset(['editingBannerId', 'title', 'subtitle', 'link', 'startsAt', 'endsAt']);
        $this->sortOrder = '1';
        $this->showText = true;
        $this->bannerStatus = BannerStatusEnum::Active->value;
        $this->resetValidation();
    }

    private function resetMediaUpload(): void
    {
        $this->reset(['mediaBannerId', 'imageUpload', 'removeImage']);
        $this->resetValidation();
    }
}
