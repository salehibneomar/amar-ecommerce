@props([
    'categories',
    'hasMore' => false,
])

<div {{ $attributes }}>
    <h2 class="mb-3 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Categories') }}</h2>
    <flux:checkbox.group wire:model.live="categoryIds" class="space-y-2.5 [&_[data-flux-checkbox-indicator]]:size-5 [&_[data-flux-label]]:text-[15px] [&_[data-flux-label]]:text-zinc-600 dark:[&_[data-flux-label]]:text-zinc-300">
        <x-frontend.shop.category-checkbox-tree :categories="$categories" />
    </flux:checkbox.group>
    @if ($hasMore)
        <button type="button" wire:click="loadMoreCategories" class="mt-3 text-sm font-medium text-brand-text hover:underline">{{ __('Load more') }}</button>
    @endif
</div>
