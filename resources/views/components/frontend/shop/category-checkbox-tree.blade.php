@props([
    'categories',
])

@foreach ($categories as $category)
    <div wire:key="category-{{ $category['id'] }}" class="space-y-2">
        <flux:checkbox :value="$category['id']" :label="$category['name']" />
        @if ($category['children'])
            <div class="ms-6 space-y-2">
                <x-frontend.shop.category-checkbox-tree :categories="$category['children']" />
            </div>
        @endif
    </div>
@endforeach
