@props([
    'paginator',
    'summary' => __('Showing :from to :to of :total results'),
])

@if ($paginator->hasPages())
    <div class="flex flex-col items-start gap-2">
        <nav class="flex items-center gap-1" aria-label="{{ __('Pagination') }}">
            <x-ui.button
                size="sm"
                variant="subtle"
                icon="chevron-left"
                wire:click="previousPage"
                :disabled="$paginator->onFirstPage()"
                class="!min-w-8 !px-2 !shadow-none"
                aria-label="{{ __('Previous page') }}"
            />

            @foreach (paginationPages($paginator->currentPage(), $paginator->lastPage()) as $page)
                @if ($page === null)
                    <span class="px-1 text-zinc-400">...</span>
                    @continue
                @endif

                <x-ui.button
                    size="sm"
                    :variant="$page === $paginator->currentPage() ? 'primary' : 'subtle'"
                    wire:click="setPage({{ $page }})"
                    class="!min-w-8 !px-2 !shadow-none"
                    aria-current="{{ $page === $paginator->currentPage() ? 'page' : 'false' }}"
                >
                    {{ $page }}
                </x-ui.button>
            @endforeach

            <x-ui.button
                size="sm"
                variant="subtle"
                icon="chevron-right"
                wire:click="nextPage"
                :disabled="! $paginator->hasMorePages()"
                class="!min-w-8 !px-2 !shadow-none"
                aria-label="{{ __('Next page') }}"
            />
        </nav>

        <flux:text class="!text-sm !text-zinc-600 dark:!text-zinc-300">
            {{ __($summary, [
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ]) }}
        </flux:text>
    </div>
@else
    <flux:text class="!text-sm !text-zinc-600 dark:!text-zinc-300">
        {{ __($summary, [
            'from' => $paginator->firstItem() ?? 0,
            'to' => $paginator->lastItem() ?? 0,
            'total' => $paginator->total(),
        ]) }}
    </flux:text>
@endif
