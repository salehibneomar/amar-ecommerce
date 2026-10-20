<?php

namespace App\Services\Frontend\Shop;

use App\Enums\Catalog\CategoryStatusEnum;
use App\Enums\Catalog\ProductStatusEnum;
use App\Enums\Content\BannerStatusEnum;
use App\Enums\Content\FaqStatusEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Offer;
use App\Models\Product;
use App\Traits\Cache\FrontendCacheTrait;
use App\Traits\Query\CategoryTreeTrait;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorInstance;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;

class PageService
{
    use CategoryTreeTrait, FrontendCacheTrait;

    private const CATEGORY_COLUMNS = ['id', 'name', 'parent_id', 'is_featured'];

    private const FEATURED_PRODUCTS_LIMIT = 8;

    private const NEW_ARRIVALS_LIMIT = 8;

    private const TITLE_SEARCH_LIMIT = 5;

    private const FINAL_PRICE_SQL = 'ROUND(price * (100 - COALESCE(discount_percentage, 0)) / 100.0)';

    private const FAQS_LIMIT = 15;

    private const PRODUCTS_PER_PAGE = 12;

    private const LISTED_STATUSES = [ProductStatusEnum::InStock, ProductStatusEnum::OutOfStock];

    /**
     * @return Collection<int, Banner>
     */
    public function getActiveBanners(): Collection
    {
        $payloads = $this->rememberFrontendCache(
            FrontendCacheKeyEnum::Banners,
            fn (): array => Banner::query()
                ->with('media')
                ->where('status', BannerStatusEnum::Active)
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get()
                ->map(fn (Banner $banner): array => $this->toFrontendCachePayload($banner, 'media'))
                ->all(),
        );

        return $this->collectFromFrontendCachePayloads(Banner::class, $payloads)
            ->filter(fn (Banner $banner): bool => $banner->isCurrentlyActive())
            ->values();
    }

    /**
     * @return list<array{image: string|null, title: string, subtitle: string|null, show_text: bool, link: string|null}>
     */
    public function getBannerSlides(): array
    {
        return array_values($this->getActiveBanners()
            ->map(fn (Banner $banner): array => [
                'image' => $banner->image_url,
                'title' => $banner->title,
                'subtitle' => $banner->subtitle,
                'show_text' => $banner->show_text,
                'link' => $banner->link,
            ])
            ->all());
    }

    /**
     * @return array{items: list<array{id: int, name: string, is_featured: bool, children: list<array<string, mixed>>}>, has_more: bool}
     */
    public function getCategoryTreeSlice(int $limit): array
    {
        return $this->rememberFrontendCacheVariant(FrontendCacheKeyEnum::Categories, ['roots', $limit, categoryLevel()], function () use ($limit): array {
            $roots = $this->activeCategories()->whereNull('parent_id')->limit($limit + 1)->get(self::CATEGORY_COLUMNS);

            return ['items' => $this->withSubCategories($roots->take($limit)), 'has_more' => $roots->count() > $limit];
        });
    }

    /**
     * @param  list<int>  $previous
     * @param  list<int>  $selected
     * @return list<int>
     */
    public function selectCategories(array $previous, array $selected): array
    {
        $removed = $this->categoriesWithDescendantIds(array_values(array_diff($previous, $selected)));
        $selected = [...$selected, ...$this->categoriesWithDescendantIds(array_values(array_diff($selected, $previous)), categoryLevel())];

        return array_values(array_unique(array_filter(array_diff($selected, $removed))));
    }

    /**
     * @param  list<int>  $categoryIds
     * @return list<int>
     */
    public function linkedCategories(array $categoryIds): array
    {
        $parentIds = array_merge([], ...array_map(fn (array $path): array => array_slice($path, 1), array_values($this->categoryPaths($categoryIds))));

        return array_values(array_unique([...$categoryIds, ...$this->selectCategories([], array_values(array_diff($categoryIds, $parentIds)))]));
    }

    /**
     * @return list<array{id: int, name: string, is_featured: bool, children: list<array<string, mixed>>, image_url: string|null}>
     */
    public function getFeaturedCategories(): array
    {
        return $this->rememberFrontendCacheVariant(
            FrontendCacheKeyEnum::Categories,
            ['featured', categoryLevel()],
            function (): array {
                $roots = $this->activeCategories()->whereNull('parent_id')->where('is_featured', true)->with('media')->get(self::CATEGORY_COLUMNS);

                return array_map(fn (array $category): array => [
                    ...$category,
                    'image_url' => $roots->find($category['id'])?->mediaUrl('category.media.image', 'regular'),
                ], $this->withSubCategories($roots));
            },
        );
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function getCategoryTrail(?int $categoryId): array
    {
        $ids = $this->categoryLevelIds($categoryId);
        $names = Category::query()->whereKey($ids)->where('status', CategoryStatusEnum::Active)->pluck('name', 'id');

        return array_values(array_map(fn (int $id): array => ['id' => $id, 'name' => (string) $names[$id]], array_filter($ids, fn (int $id): bool => $names->has($id))));
    }

    /**
     * @return Collection<int, Product>
     */
    public function getFeaturedProducts(): Collection
    {
        return $this->cachedProducts(['featured'], fn (Builder $query): Builder => $query->where('is_featured', true)->limit(self::FEATURED_PRODUCTS_LIMIT));
    }

    /**
     * @return Collection<int, Product>
     */
    public function getNewArrivals(int $limit = self::NEW_ARRIVALS_LIMIT): Collection
    {
        return $this->cachedProducts(['new', $limit], fn (Builder $query): Builder => $query->limit($limit));
    }

    /**
     * @return Collection<int, Product>
     */
    public function searchProductsByTitle(string $title, int $limit = self::TITLE_SEARCH_LIMIT): Collection
    {
        $title = trim($title);

        if ($title === '') {
            return (new Product)->newCollection();
        }

        return Product::query()
            ->with('media')
            ->where('status', ProductStatusEnum::InStock)
            ->where(fn (Builder $query): Builder => $query->where('name', 'like', "%{$title}%")->orWhere('sku', $title))
            ->latest()
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getListedProducts(): Collection
    {
        return $this->cachedProducts(['listed'], fn (Builder $query): Builder => $query, self::LISTED_STATUSES);
    }

    public function countProducts(): int
    {
        return $this->rememberFrontendCacheVariant(FrontendCacheKeyEnum::Products, ['count'], fn (): int => Product::query()->where('status', ProductStatusEnum::InStock)->count());
    }

    /**
     * @return list<array{slug: string, updated_at: string|null}>
     */
    public function getSitemapProducts(): array
    {
        return $this->rememberFrontendCacheVariant(FrontendCacheKeyEnum::Products, ['sitemap'], fn (): array => array_values(Product::query()
            ->whereIn('status', self::LISTED_STATUSES)
            ->orderByDesc('id')
            ->get(['id', 'slug', 'updated_at', 'type'])
            ->map(fn (Product $product): array => ['slug' => $product->slug, 'updated_at' => $product->updated_at?->toAtomString()])
            ->all()));
    }

    /**
     * @return Collection<int, Offer>
     */
    public function getActiveOffers(): Collection
    {
        $payloads = $this->rememberFrontendCache(
            FrontendCacheKeyEnum::Offers,
            fn (): array => Offer::query()
                ->with('categories:id,name')
                ->where('is_active', true)
                ->orderByDesc('discount_percentage')
                ->get()
                ->map(fn (Offer $offer): array => $this->toFrontendCachePayload($offer, 'categories'))
                ->all(),
        );

        return $this->collectFromFrontendCachePayloads(Offer::class, $payloads)
            ->filter(fn (Offer $offer): bool => $offer->isCurrentlyActive() && (! isGlobalOfferOnly() || $offer->isGlobal()))
            ->values();
    }

    /**
     * @param  array<int, int|null>  $categoryIds
     * @return array<int, Offer|null>
     */
    public function bestOffersFor(array $categoryIds): array
    {
        $offers = $this->getActiveOffers();
        $paths = $offers->contains(fn (Offer $offer): bool => ! $offer->isGlobal()) ? $this->categoryPaths($categoryIds) : [];
        $best = [];

        foreach ($categoryIds as $categoryId) {
            $best[(int) $categoryId] ??= $offers->first(fn (Offer $offer): bool => $offer->appliesToCategoryPath($paths[(int) $categoryId] ?? []));
        }

        return $best;
    }

    public function getOfferFor(Product $product): ?Offer
    {
        return $this->bestOffersFor([$product->category_id])[(int) $product->category_id] ?? null;
    }

    /**
     * @param  list<int>  $categoryIds
     * @return LengthAwarePaginator<int, Product>
     */
    public function searchProducts(string $search = '', array $categoryIds = [], string $sort = 'latest', ?int $minPrice = null, ?int $maxPrice = null, ?int $page = null): LengthAwarePaginator
    {
        $search = trim($search);
        $page ??= Paginator::resolveCurrentPage();
        sort($categoryIds);

        $load = function () use ($search, $categoryIds, $sort, $minPrice, $maxPrice, $page): array {
            $paginator = Product::query()
                ->with('media')
                ->whereIn('status', self::LISTED_STATUSES)
                ->when($search !== '', fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%")->orWhere('sku', $search)))
                ->when($categoryIds !== [], fn (Builder $query): Builder => $query->whereIn('category_id', $categoryIds))
                ->when($minPrice !== null, fn (Builder $query): Builder => $query->whereRaw(self::FINAL_PRICE_SQL.' >= ?', [$minPrice]))
                ->when($maxPrice !== null, fn (Builder $query): Builder => $query->whereRaw(self::FINAL_PRICE_SQL.' <= ?', [$maxPrice]))
                ->when($sort === 'price_asc', fn (Builder $query): Builder => $query->orderBy('price'))
                ->when($sort === 'price_desc', fn (Builder $query): Builder => $query->orderByDesc('price'))
                ->latest()
                ->orderByDesc('id')
                ->paginate(self::PRODUCTS_PER_PAGE, ['*'], 'page', $page);

            return [
                'items' => $paginator->getCollection()->map(fn (Product $product): array => $this->toFrontendCachePayload($product, 'media'))->all(),
                'total' => $paginator->total(),
            ];
        };

        $result = $search === ''
            ? $this->rememberFrontendCacheVariant(FrontendCacheKeyEnum::Products, ['shop', $categoryIds, $sort, $minPrice, $maxPrice, $page, categoryLevel()], $load)
            : $load();

        return new LengthAwarePaginatorInstance(
            $this->collectFromFrontendCachePayloads(Product::class, $result['items']),
            $result['total'],
            self::PRODUCTS_PER_PAGE,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'],
        );
    }

    public function getProductBySlug(string $slug): Product
    {
        return Product::query()
            ->with(['media', 'category'])
            ->where('slug', $slug)
            ->where('status', '!=', ProductStatusEnum::Archived)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getRelatedProducts(Product $product): Collection
    {
        if ($product->category_id === null) {
            return new Collection;
        }

        return Product::query()
            ->with('media')
            ->where('status', ProductStatusEnum::InStock)
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->latest()
            ->limit(4)
            ->get();
    }

    /**
     * @return array<int, array{id: int, title: string, description: string}>
     */
    public function getFaqs(): array
    {
        return $this->rememberFrontendCache(
            FrontendCacheKeyEnum::Faqs,
            fn (): array => Faq::query()
                ->where('status', FaqStatusEnum::Active)
                ->orderBy('id')
                ->limit(self::FAQS_LIMIT)
                ->get(['id', 'title', 'description'])
                ->map(fn (Faq $faq): array => $faq->only(['id', 'title', 'description']))
                ->all(),
        );
    }

    /**
     * @return array<int, array{icon: string, title: string, value: string, href: string, action: string}>
     */
    public function getContactChannels(): array
    {
        $whatsappNumber = shopWhatsappNumber();
        $facebookPageUrl = shopFacebookPageUrl();
        $facebookGroupUrl = shopFacebookGroupUrl();
        $supportEmail = shopSupportEmail();

        return array_values(array_filter([
            $whatsappNumber ? ['icon' => 'chat', 'title' => __('WhatsApp'), 'value' => '+'.$whatsappNumber, 'href' => 'https://wa.me/'.$whatsappNumber, 'action' => __('Chat on WhatsApp')] : null,
            $facebookPageUrl ? ['icon' => 'thumb_up', 'title' => __('Facebook page'), 'value' => $this->displayUrl($facebookPageUrl), 'href' => $facebookPageUrl, 'action' => __('Visit page')] : null,
            $facebookGroupUrl ? ['icon' => 'groups', 'title' => __('Facebook group'), 'value' => $this->displayUrl($facebookGroupUrl), 'href' => $facebookGroupUrl, 'action' => __('Join group')] : null,
            $supportEmail ? ['icon' => 'mail', 'title' => __('Email'), 'value' => $supportEmail, 'href' => 'mailto:'.$supportEmail, 'action' => __('Send email')] : null,
        ]));
    }

    /**
     * @param  array<int|string, mixed>  $variant
     * @param  Closure(Builder<Product>): Builder<Product>  $scope
     * @param  list<ProductStatusEnum>  $statuses
     * @return Collection<int, Product>
     */
    private function cachedProducts(array $variant, Closure $scope, array $statuses = [ProductStatusEnum::InStock]): Collection
    {
        return $this->collectFromFrontendCachePayloads(Product::class, $this->rememberFrontendCacheVariant(
            FrontendCacheKeyEnum::Products,
            $variant,
            function () use ($scope, $statuses): array {
                $query = Product::query()->with('media');
                $query->whereIn('status', $statuses)->latest()->orderByDesc('id');

                return $scope($query)
                    ->get()
                    ->map(fn (Product $product): array => $this->toFrontendCachePayload($product, 'media'))
                    ->all();
            },
        ));
    }

    /**
     * @return Builder<Category>
     */
    private function activeCategories(): Builder
    {
        return Category::query()->where('status', CategoryStatusEnum::Active)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  Collection<int, Category>  $roots
     * @return list<array{id: int, name: string, is_featured: bool, children: list<array<string, mixed>>}>
     */
    private function withSubCategories(Collection $roots): array
    {
        $rows = $roots;
        $frontier = $roots->modelKeys();

        for ($level = 2; $level <= categoryLevel() && $frontier !== []; $level++) {
            $children = $this->activeCategories()->whereIn('parent_id', $frontier)->get(self::CATEGORY_COLUMNS);
            $rows = $rows->concat($children);
            $frontier = $children->modelKeys();
        }

        return $this->categoryBranch($rows->groupBy(fn (Category $category): string => (string) $category->parent_id)->all());
    }

    /**
     * @param  array<int|string, Collection<int, Category>>  $byParent
     * @return list<array{id: int, name: string, is_featured: bool, children: list<array<string, mixed>>}>
     */
    private function categoryBranch(array $byParent, ?int $parentId = null): array
    {
        return array_values(($byParent[(string) $parentId] ?? new Collection)
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'is_featured' => $category->is_featured,
                'children' => $this->categoryBranch($byParent, $category->id),
            ])
            ->all());
    }

    private function displayUrl(string $url): string
    {
        return rtrim(Str::after($url, '://'), '/');
    }
}
