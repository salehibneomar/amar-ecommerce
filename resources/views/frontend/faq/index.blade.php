<x-layouts.frontend :title="__('FAQ')">
    <x-frontend.layout.page :title="__('Frequently asked questions')" :subtitle="__('Quick answers about orders, delivery and payments.')"
        x-data="{
            search: '',
            titles: {{ Js::from(array_column($faqs, 'title')) }},
            matches(title) {
                return title.toLowerCase().includes(this.search.trim().toLowerCase());
            },
            get hasResults() {
                return this.titles.some((title) => this.matches(title));
            },
        }">
        @if ($faqs)
            <flux:input x-model="search" icon="magnifying-glass" :placeholder="__('Search questions...')" :aria-label="__('Search questions')" clearable />
        @endif

        <div class="flex flex-col gap-3">
            @forelse ($faqs as $faq)
                <x-frontend.content.faq-item x-show="matches({{ Js::from($faq['title']) }})" :question="$faq['title']" :answer="$faq['description']" />
            @empty
                <flux:text>{{ __('Questions will appear here soon.') }}</flux:text>
            @endforelse

            @if ($faqs)
                <flux:text x-show="! hasResults" x-cloak class="py-6 text-center">{{ __('No questions match your search.') }}</flux:text>
            @endif
        </div>

        <x-frontend.content.contact-cta :title="__('Still have a question?')" :description="__('Reach us on WhatsApp, Facebook or email and we will help you out.')" />
    </x-frontend.layout.page>
</x-layouts.frontend>
