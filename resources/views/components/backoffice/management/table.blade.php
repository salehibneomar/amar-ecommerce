@props([
    'columns' => [],
    'sortable' => [],
    'sortBy' => null,
    'sortDirection' => null,
])

<flux:card {{ $attributes->class(['min-w-0 border-line bg-surface shadow-card']) }}>
    @isset($toolbar)
        <div class="flex flex-col justify-between gap-3 border-b border-zinc-200 py-3 dark:border-zinc-700 md:flex-row md:items-end md:[&>div]:items-end max-md:[&>div]:w-full max-md:[&>div]:flex-col max-md:[&>div]:items-stretch max-md:[&_[data-flux-field]]:w-full max-md:[&_input]:max-w-none">
            {{ $toolbar }}
        </div>
    @endisset

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 border-b border-zinc-200 py-3 dark:border-zinc-700">
            {{ $actions }}
        </div>
    @endisset

    <div class="overflow-x-auto">
    <flux:table>
        <flux:table.columns>
            @foreach ($columns as $column => $label)
                @if (in_array($column, $sortable, true))
                    <flux:table.column
                        sortable
                        :sorted="$sortBy === $column"
                        :direction="$sortDirection"
                        @class([
                            "[&_[data-flux-table-sortable]_.opacity-0]:!opacity-100",
                            "font-bold text-zinc-950 dark:text-white [&_[data-flux-table-sortable]>div:last-child]:text-zinc-950 dark:[&_[data-flux-table-sortable]>div:last-child]:text-white" => $sortBy === $column,
                        ])
                        wire:click="sort('{{ $column }}')"
                    >
                        <span @class(['font-bold text-zinc-950 dark:text-white' => $sortBy === $column])>
                            {{ $label }}
                        </span>
                    </flux:table.column>
                @else
                    <flux:table.column>
                        {{ $label }}
                    </flux:table.column>
                @endif
            @endforeach
        </flux:table.columns>
        <flux:table.rows>
            {{ $rows }}
        </flux:table.rows>
    </flux:table>
    </div>

    @isset($pagination)
        <div class="mt-4 flex justify-start">
            <div>{{ $pagination }}</div>
        </div>
    @endisset
</flux:card>
