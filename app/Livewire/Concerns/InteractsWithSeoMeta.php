<?php

namespace App\Livewire\Concerns;

use App\Interfaces\Seo\SeoMetaInterface;
use App\Services\Backoffice\Seo\SeoMetaService;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait InteractsWithSeoMeta
{
    use WithFileUploads;

    public bool $showSeoMetaForm = false;

    #[Locked]
    public ?int $seoMetaOwnerId = null;

    public string $seoMetaTitle = '';

    public string $seoMetaDescription = '';

    public bool $seoMetaNoindex = false;

    public ?TemporaryUploadedFile $seoMetaImage = null;

    public bool $removeSeoMetaImage = false;

    protected SeoMetaService $seoMetaService;

    public function bootInteractsWithSeoMeta(SeoMetaService $seoMetaService): void
    {
        $this->seoMetaService = $seoMetaService;
    }

    /**
     * @return class-string<Model&SeoMetaInterface>
     */
    abstract protected function seoMetaOwnerType(): string;

    protected function hasSeoMetaImage(): bool
    {
        return false;
    }

    public function editSeoMeta(int $ownerId): void
    {
        $this->resetSeoMeta();
        $this->fillSeoMeta($ownerId);
        $this->showSeoMetaForm = true;
    }

    public function updatedShowSeoMetaForm(bool $showSeoMetaForm): void
    {
        if (! $showSeoMetaForm) {
            $this->resetSeoMeta();
        }
    }

    public function saveSeoMeta(): void
    {
        $this->validate($this->seoMetaRules(), [], $this->seoMetaValidationAttributes());

        $this->storeSeoMeta();

        $this->showSeoMetaForm = false;
        $this->resetSeoMeta();

        $this->dispatch('toast-show', slots: ['text' => __('SEO saved.')], dataset: ['variant' => 'success']);
    }

    /**
     * @return array{image: bool, accept: string, hint: string, media: Media|null, previewUrl: string|null}|null
     */
    #[Computed]
    public function seoMetaImageField(): ?array
    {
        return $this->seoMetaOwnerId && $this->hasSeoMetaImage() ? $this->seoMetaService->imageField($this->seoMetaOwnerType(), $this->seoMetaOwnerId) : null;
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function seoMetaRules(): array
    {
        return [
            'seoMetaTitle' => ['nullable', 'string', 'max:70'],
            'seoMetaDescription' => ['nullable', 'string', 'max:160'],
            'seoMetaNoindex' => ['boolean'],
            'seoMetaImage' => mediaValidationRules('seo.media.image'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function seoMetaValidationAttributes(): array
    {
        return [
            'seoMetaTitle' => __('SEO title'),
            'seoMetaDescription' => __('SEO description'),
            'seoMetaImage' => __('share image'),
        ];
    }

    protected function fillSeoMeta(int $ownerId): void
    {
        $values = $this->seoMetaService->formValues($this->seoMetaOwnerType(), $ownerId);

        $this->seoMetaOwnerId = $ownerId;
        $this->seoMetaTitle = $values['title'];
        $this->seoMetaDescription = $values['description'];
        $this->seoMetaNoindex = $values['noindex'];
        unset($this->seoMetaImageField);
    }

    protected function storeSeoMeta(): void
    {
        if (! $this->seoMetaOwnerId) {
            return;
        }

        $this->seoMetaService->save(
            $this->seoMetaOwnerType(),
            $this->seoMetaOwnerId,
            ['title' => $this->seoMetaTitle, 'description' => $this->seoMetaDescription, 'noindex' => $this->seoMetaNoindex],
            $this->hasSeoMetaImage() ? $this->seoMetaImage : null,
            $this->hasSeoMetaImage() && $this->removeSeoMetaImage,
        );

        $this->reset(['seoMetaImage', 'removeSeoMetaImage']);
        unset($this->seoMetaImageField);
    }

    protected function resetSeoMeta(): void
    {
        $this->reset(['seoMetaOwnerId', 'seoMetaTitle', 'seoMetaDescription', 'seoMetaNoindex', 'seoMetaImage', 'removeSeoMetaImage']);
        unset($this->seoMetaImageField);
        $this->resetValidation();
    }
}
