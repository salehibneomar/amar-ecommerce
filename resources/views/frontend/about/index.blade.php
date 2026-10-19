<x-layouts::frontend :title="__('About')">
    <x-frontend.layout.page :title="__('About us')" :subtitle="__('Practical Bangla books for the new generation of farmers.')">
        <x-frontend.cards.card :title="__('Our story')">
            <div class="space-y-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                <p>{{ __('Good farming knowledge is often scattered, outdated or hard to find in Bangla. We started নতুন কৃষক to put practical books on crops, livestock, fisheries and rural life in one place, so anyone can learn at their own pace.') }}</p>
                <p>{{ __('Most of our books are digital, so you can read them on your phone the moment your payment is verified. Some titles are also available in print.') }}</p>
            </div>
        </x-frontend.cards.card>

        <div class="grid gap-5 sm:grid-cols-3">
            <x-frontend.cards.feature-card icon="target" :title="__('Our mission')" :description="__('Make quality agricultural knowledge simple, affordable and available to every farmer.')" />
            <x-frontend.cards.feature-card icon="menu_book" :title="__('What we offer')" :description="__('Digital and printed books you can read on any device, anytime.')" />
            <x-frontend.cards.feature-card icon="favorite" :title="__('Why readers trust us')" :description="__('Practical content, fair prices and real people ready to help.')" />
        </div>

        <x-frontend.content.contact-cta :title="__('Have a question?')" :description="__('We are happy to help with anything about books, orders or payments.')" />
    </x-frontend.layout.page>
</x-layouts::frontend>
