<x-layouts::frontend :title="__('My library')">
    <x-frontend.layout.page :title="__('My library')" :subtitle="__('Books from your paid orders. Free books can be read from the shop any time.')">
        @if ($books->isEmpty())
            <div class="flex flex-col items-center gap-4 rounded-xl border border-dashed border-zinc-300 px-6 py-16 text-center dark:border-zinc-700">
                <x-ui.icon-badge size="2xl" circle>
                    <span class="material-symbols-outlined text-3xl!">local_library</span>
                </x-ui.icon-badge>
                <div>
                    <p class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Your library is empty') }}</p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Books appear here as soon as we verify your payment.') }}</p>
                </div>
                <x-ui.button variant="primary" :href="route('shop')" wire:navigate>{{ __('Shop') }}</x-ui.button>
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4 xl:grid-cols-5">
                @foreach ($books as $book)
                    <x-frontend.cards.product-tile :product="$book" owned />
                @endforeach
            </div>
            {{ $books->links() }}
        @endif
    </x-frontend.layout.page>

    <livewire:frontend.reader.index />
</x-layouts::frontend>
