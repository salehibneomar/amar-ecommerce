@props([
    'title' => null,
    'documentId' => null,
    'viewerKey' => null,
    'height' => 'calc(100dvh - 13rem)',
])

<x-ui.modal {{ $attributes->class(['w-full max-w-[calc(100vw-2rem)] sm:max-w-5xl']) }}>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 pe-8">
            <flux:heading size="lg" class="min-w-0 truncate">{{ $title }}</flux:heading>

            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>

        {{ $notice ?? '' }}

        @if ($documentId && $viewerKey)
            <livewire:pdf-viewer
                :document-id="$documentId"
                :height="$height"
                theme="soft"
                :initial-page="1"
                :show-toolbar="true"
                :key="'pdf-viewer-'.$viewerKey"
            />
        @endif
    </div>
</x-ui.modal>
