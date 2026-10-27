@props([
    'title' => null,
    'subtitle' => null,
    'submit' => null,
    'submitLabel' => __('Save'),
    'closeLabel' => __('Close'),
    'showDefaultFooter' => true,
    'size' => 'md',
])

<x-ui.modal :closable="false" {{ $attributes->class([
    'max-w-[calc(100vw-2rem)] overflow-x-hidden',
    'sm:w-sm sm:max-w-sm' => $size === 'sm',
    'sm:w-md sm:max-w-md' => $size === 'md' || ! in_array($size, ['sm', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl'], true),
    'sm:w-lg sm:max-w-lg' => $size === 'lg',
    'sm:w-xl sm:max-w-xl' => $size === 'xl',
    'sm:w-2xl sm:max-w-2xl' => $size === '2xl',
    'sm:w-3xl sm:max-w-3xl' => $size === '3xl',
    'sm:w-4xl sm:max-w-4xl' => $size === '4xl',
    'sm:w-5xl sm:max-w-5xl' => $size === '5xl',
]) }}>
    <form @if ($submit) wire:submit="{{ $submit }}" @endif class="min-w-0 space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 break-words">
                <flux:heading size="lg">
                    @isset($heading)
                        {{ $heading }}
                    @else
                        {{ $title }}
                    @endisset
                </flux:heading>
                @if ($subtitle)
                    <flux:text class="mt-1">{{ $subtitle }}</flux:text>
                @endif
            </div>

            <div class="flex shrink-0 items-center gap-2">
                @isset($headerActions)
                    {{ $headerActions }}
                @endisset
                <flux:modal.close>
                    <x-ui.button
                        variant="ghost"
                        icon="x-mark"
                        size="sm"
                        aria-label="{{ __('Close modal') }}"
                        class="text-zinc-400! hover:text-zinc-800! dark:text-zinc-500! dark:hover:text-white!"
                    />
                </flux:modal.close>
            </div>
        </div>

        {{ $slot }}

        @isset($footer)
            {{ $footer }}
        @elseif ($showDefaultFooter)
            <x-form.actions modal>
                @isset($footerActions)
                    <div class="flex flex-col gap-2 sm:me-auto sm:flex-row">{{ $footerActions }}</div>
                @endisset
                <flux:modal.close>
                    <x-ui.button type="button" class="w-full sm:w-auto">{{ $closeLabel }}</x-ui.button>
                </flux:modal.close>
                <x-ui.button type="submit" variant="primary">{{ $submitLabel }}</x-ui.button>
            </x-form.actions>
        @endisset
    </form>
</x-ui.modal>
