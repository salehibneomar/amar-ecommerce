@props([
    'paginator',
    'summary' => __('Showing :from to :to of :total results'),
])

@if ($paginator->hasPages())
    <div class="flex flex-col items-start gap-2">
        <nav class="flex items-center gap-1" aria-label="{{ __('Pagination') }}">
            <flux:button
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

                <flux:button
                    size="sm"
                    variant="subtle"
                    wire:click="setPage({{ $page }})"
                    class="{{ $page === $paginator->currentPage()
                        ? '!min-w-8 !border-brand-600 !bg-transparent !px-2 !text-brand-700 !shadow-none dark:!border-brand-400 dark:!bg-transparent dark:!text-brand-300'
                        : '!min-w-8 !px-2 !shadow-none' }}"
                    aria-current="{{ $page === $paginator->currentPage() ? 'page' : 'false' }}"
                >
                    {{ $page }}
                </flux:button>
            @endforeach

            <flux:button
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
