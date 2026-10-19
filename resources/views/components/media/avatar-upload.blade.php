@props([
    'name',
    'url' => null,
    'upload' => 'avatarUpload',
    'remove' => 'removeAvatar',
    'accept' => 'image/*',
    'label' => __('Profile photo'),
])

<flux:field>
    <flux:label>{{ $label }}</flux:label>

    <div
        class="relative w-fit"
        x-data="{ uploading: false }"
        x-on:livewire-upload-start="uploading = true"
        x-on:livewire-upload-finish="uploading = false"
        x-on:livewire-upload-error="uploading = false"
    >
        <flux:avatar :src="$url" :name="$name" class="size-28! rounded-xl! text-2xl!" />

        <div x-show="uploading" x-cloak class="absolute inset-0 flex items-center justify-center rounded-xl bg-black/40">
            <flux:icon.loading class="size-6 text-white" />
        </div>

        <input x-ref="imageInput" type="file" wire:model="{{ $upload }}" accept="{{ $accept }}" class="sr-only" tabindex="-1" />

        <div class="absolute -end-2 -bottom-0.5">
            @if ($url)
                <x-ui.dropdown>
                    <x-slot:trigger>
                        <flux:button size="sm" square icon="camera" class="rounded-full! border-0! bg-brand-600! text-white! shadow-md ring-2 ring-white hover:bg-brand-700! dark:ring-zinc-900" :aria-label="__('Change photo')" />
                    </x-slot:trigger>

                    <flux:menu.item icon="arrow-up-tray" x-on:click="$refs.imageInput.click()">{{ __('Upload new photo') }}</flux:menu.item>
                    <flux:menu.item icon="trash" variant="danger" wire:click="{{ $remove }}">{{ __('Remove photo') }}</flux:menu.item>
                </x-ui.dropdown>
            @else
                <flux:button size="sm" square icon="camera" class="rounded-full! border-0! bg-brand-600! text-white! shadow-md ring-2 ring-white hover:bg-brand-700! dark:ring-zinc-900" x-on:click="$refs.imageInput.click()" :aria-label="__('Upload photo')" />
            @endif
        </div>
    </div>

    <flux:error :name="$upload" />
</flux:field>
