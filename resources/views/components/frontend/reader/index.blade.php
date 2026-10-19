<div>
    <x-media.pdf-viewer-modal wire:model.live="showReader" :title="$bookTitle" :document-id="$documentId" :viewer-key="$viewerKey">
        @if ($this->downloadUrl)
            <x-slot:actions>
                <flux:button size="sm" icon="arrow-down-tray" wire:click="$set('showDownloadWarning', true)" data-test="book-download-button">{{ __('Download') }}</flux:button>
            </x-slot:actions>
        @endif

        @if ($isPreview)
            <x-slot:notice>
                <x-frontend.content.notice data-test="book-preview-notice">
                    {{ $this->isPhysicalOnly ? __('Free preview: sample pages from the printed book') : __('Free preview: the first :pages pages', ['pages' => bookPreviewPages()]) }}
                    <x-slot:action>
                        <flux:button size="sm" icon="shopping-cart" x-on:click="$wire.set('showReader', false); Livewire.dispatch('add-to-cart', { productId: {{ (int) $bookId }}, buyNow: true })">{{ __('Buy this book') }}</flux:button>
                    </x-slot:action>
                </x-frontend.content.notice>
            </x-slot:notice>
        @endif
    </x-media.pdf-viewer-modal>

    <x-ui.modal wire:model="showDownloadWarning" class="w-full max-w-[calc(100vw-2rem)] sm:max-w-md">
        <div class="space-y-5">
            <div class="flex items-start gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400">
                    <span class="material-symbols-outlined">gavel</span>
                </span>
                <div>
                    <flux:heading size="lg">{{ __('Before you download') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('This book is for your personal study only. Selling, sharing, reprinting or using it for any business purpose is strictly prohibited.') }}</flux:text>
                    <flux:text class="mt-2 font-medium text-red-600! dark:text-red-400!">{{ __('If any misuse is found, necessary legal action will be taken.') }}</flux:text>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <flux:modal.close>
                    <flux:button>{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                @if ($this->downloadUrl)
                    <flux:button variant="primary" icon="arrow-down-tray" :href="$this->downloadUrl" x-on:click="$wire.set('showDownloadWarning', false)" data-test="book-download-confirm">{{ __('I agree, download') }}</flux:button>
                @endif
            </div>
        </div>
    </x-ui.modal>
</div>
