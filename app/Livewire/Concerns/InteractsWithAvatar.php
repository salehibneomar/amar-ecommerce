<?php

namespace App\Livewire\Concerns;

use App\Services\Frontend\Account\AccountService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

trait InteractsWithAvatar
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $avatarUpload = null;

    public function updatedAvatarUpload(): void
    {
        $this->validate(
            ['avatarUpload' => mediaValidationRules('user.media.avatar', required: true)],
            ['avatarUpload.max' => __('Keep your photo under :size MB.', ['size' => mediaMaxSizeInMb('user.media.avatar')])],
        );

        app(AccountService::class)->updateAvatar(Auth::user(), $this->avatarUpload);

        $this->reset('avatarUpload');
        unset($this->avatarUrl);
        $this->dispatch('toast-show', slots: ['text' => __('Profile photo updated.')], dataset: ['variant' => 'success']);
    }

    public function removeAvatar(): void
    {
        app(AccountService::class)->updateAvatar(Auth::user(), null);

        unset($this->avatarUrl);
        $this->dispatch('toast-show', slots: ['text' => __('Profile photo removed.')], dataset: ['variant' => 'success']);
    }

    #[Computed]
    public function avatarUrl(): ?string
    {
        return Auth::user()?->avatar_url;
    }
}
