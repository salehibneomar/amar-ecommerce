<x-layouts::frontend :title="__('Contact')">
    <x-frontend.layout.page :title="__('Contact us')" :subtitle="__('Questions about books, payments or your access? Reach us on any channel below.')">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($channels as $channel)
                <x-frontend.cards.contact-card :icon="$channel['icon']" :title="$channel['title']" :value="$channel['value']" :href="$channel['href']" :action="$channel['action']" />
            @endforeach
        </div>

        <x-frontend.cards.card :title="__('Business hours')" :description="__('Bangladesh time (GMT+6). We are open every day, including weekends and holidays.')">
            <dl class="divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 py-3">
                    <dt class="text-zinc-600 dark:text-zinc-400">{{ __('Every day') }}</dt>
                    <dd class="font-medium text-zinc-900 dark:text-white">{{ __('9:00 AM - 11:00 PM') }}</dd>
                </div>
            </dl>
            <p class="flex items-start gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <span class="material-symbols-outlined text-lg text-amber-500">schedule</span>
                {{ __('Replies may be delayed during prayer times and breakfast, lunch and dinner hours.') }}
            </p>
        </x-frontend.cards.card>
    </x-frontend.layout.page>
</x-layouts::frontend>
