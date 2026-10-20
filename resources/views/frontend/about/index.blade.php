<x-layouts::frontend :title="__('About')">
    <x-frontend.layout.page :title="__('About us')" :subtitle="__('Quality products and honest service, delivered to your door.')">
        <x-frontend.cards.card :title="__('Our story')">
            <div class="space-y-4 text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">
                <p>{{ __(':app started with a simple idea: shopping online should be easy, fair and reliable. We pick products we would use ourselves and keep the prices honest.', ['app' => config('app.name')]) }}</p>
                <p>{{ __('Every order is checked by our team before it ships, and you can follow it from the moment you place it until it reaches you.') }}</p>
            </div>
        </x-frontend.cards.card>

        <div class="grid gap-5 sm:grid-cols-3">
            <x-frontend.cards.feature-card icon="target" :title="__('Our mission')" :description="__('Make good products simple, affordable and easy to get for everyone.')" />
            <x-frontend.cards.feature-card icon="inventory_2" :title="__('What we offer')" :description="__('A growing range of products with clear prices and home delivery.')" />
            <x-frontend.cards.feature-card icon="favorite" :title="__('Why customers trust us')" :description="__('Fair prices, careful packing and real people ready to help.')" />
        </div>

        <x-frontend.content.contact-cta :title="__('Have a question?')" :description="__('We are happy to help with anything about products, orders or payments.')" />
    </x-frontend.layout.page>
</x-layouts::frontend>
