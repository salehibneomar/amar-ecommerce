<?php

namespace App\Livewire\Backoffice\SeoMetas;

use App\Livewire\Concerns\InteractsWithSeoMeta;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    use InteractsWithSeoMeta;

    public bool $indexingEnabled = true;

    public string $googleSiteVerification = '';

    public function mount(): void
    {
        $settings = $this->seoMetaService->siteSettings();

        $this->indexingEnabled = $settings['indexing_enabled'];
        $this->googleSiteVerification = $settings['google_site_verification'] ?? '';
        $this->fillSeoMeta($this->seoMetaService->siteOwnerId());
    }

    protected function seoMetaOwnerType(): string
    {
        return $this->seoMetaService->siteOwnerType();
    }

    protected function hasSeoMetaImage(): bool
    {
        return true;
    }

    public function save(): void
    {
        $this->googleSiteVerification = trim($this->googleSiteVerification);

        $this->validate([
            'indexingEnabled' => ['boolean'],
            'googleSiteVerification' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_-]+$/'],
            ...$this->seoMetaRules(),
        ], [], [
            'googleSiteVerification' => __('Google verification code'),
            ...$this->seoMetaValidationAttributes(),
        ]);

        $this->seoMetaService->saveSiteSettings($this->indexingEnabled, $this->googleSiteVerification);
        $this->storeSeoMeta();

        $this->dispatch('toast-show', slots: ['text' => __('SEO settings saved.')], dataset: ['variant' => 'success']);
    }

    public function render(): View
    {
        return view('components.backoffice.seo-metas.index', [
            'isIndexingLocked' => ! isSeoIndexingEnabled(),
            'sitemapUrl' => route('sitemap'),
        ]);
    }
}
