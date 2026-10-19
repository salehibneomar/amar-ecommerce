<?php

namespace App\Services\Backoffice\Content;

use App\Enums\Content\OfferTypeEnum;
use App\Enums\System\FrontendCacheKeyEnum;
use App\Models\Offer;
use App\Services\Backoffice\Notification\NotificationService;
use App\Traits\Cache\FrontendCacheTrait;
use App\Traits\Query\TableQueryTrait;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfferService
{
    use FrontendCacheTrait, TableQueryTrait;

    public function __construct(private readonly NotificationService $notificationService) {}

    /**
     * @return list<string>
     */
    public function sortableColumns(): array
    {
        return ['id', 'name', 'discount_percentage', 'is_active', 'starts_at', 'ends_at', 'created_at'];
    }

    /**
     * @return LengthAwarePaginator<int, Offer>
     */
    public function getOffers(string $search = '', int $perPage = 10, string $sortBy = 'created_at', string $sortDirection = 'desc'): LengthAwarePaginator
    {
        return $this->paginateTableQuery(
            query: Offer::query(),
            search: $search,
            searchColumns: ['name'],
            filters: [],
            perPage: $perPage,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
            sortableColumns: $this->sortableColumns(),
        );
    }

    public function getOfferById(int|string $id): Offer
    {
        return Offer::query()->with('categories:id,name')->findOrFail($id);
    }

    public function findLiveGlobalOverlap(?int $ignoreId, ?string $startsAt, ?string $endsAt): ?Offer
    {
        $startsAt = ($this->parseDate($startsAt) ?? CarbonImmutable::now())->startOfDay();
        $endsAt = $this->parseDate($endsAt)?->endOfDay();

        return Offer::query()
            ->select(['id', 'name', 'starts_at', 'ends_at'])
            ->where('type', OfferTypeEnum::Global)
            ->where('is_active', true)
            ->when($ignoreId, fn (Builder $query): Builder => $query->whereKeyNot($ignoreId))
            ->where(fn (Builder $query): Builder => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $startsAt))
            ->when($endsAt, fn (Builder $query): Builder => $query->where(fn (Builder $inner): Builder => $inner->whereNull('starts_at')->orWhere('starts_at', '<=', $endsAt)))
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Offer
    {
        $this->ensureNoLiveGlobalOverlap($data);

        $offer = DB::transaction(function () use ($data): Offer {
            $offer = Offer::create($this->prepareData($data));
            $offer->categories()->sync($this->categoryIds($offer, $data));

            return $offer;
        });

        $this->forgetFrontendCache(FrontendCacheKeyEnum::Offers);

        if ($offer->is_active) {
            $this->notificationService->notifyOffer($offer);
        }

        return $offer;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int|string $id, array $data): bool
    {
        $offer = $this->getOfferById($id);
        $this->ensureNoLiveGlobalOverlap($data, $offer->id);
        $attributes = $this->prepareData($data);
        $publishes = ! $offer->is_active && $attributes['is_active'];
        $updated = DB::transaction(function () use ($offer, $attributes, $data): bool {
            $updated = $offer->update($attributes);
            $offer->categories()->sync($this->categoryIds($offer, $data));

            return $updated;
        });

        $this->forgetFrontendCache(FrontendCacheKeyEnum::Offers);

        if ($publishes) {
            $this->notificationService->notifyOffer($offer);
        }

        return $updated;
    }

    public function delete(int|string $id): bool
    {
        $deleted = (bool) $this->getOfferById($id)->delete();

        return $deleted;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureNoLiveGlobalOverlap(array $data, ?int $ignoreId = null): void
    {
        if (! ($data['is_active'] ?? false) || $this->offerType($data) !== OfferTypeEnum::Global) {
            return;
        }

        $overlap = $this->findLiveGlobalOverlap($ignoreId, $data['starts_at'] ?? null, $data['ends_at'] ?? null);

        if ($overlap) {
            throw ValidationException::withMessages([
                'is_active' => __('":name" is live in this period. Turn it off first.', ['name' => $overlap->name]),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function offerType(array $data): OfferTypeEnum
    {
        return isGlobalOfferOnly() ? OfferTypeEnum::Global : OfferTypeEnum::from($data['type']);
    }

    private function parseDate(?string $date): ?CarbonImmutable
    {
        return filled($date) ? rescue(fn (): CarbonImmutable => CarbonImmutable::parse($date), report: false) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    private function categoryIds(Offer $offer, array $data): array
    {
        return $offer->type === OfferTypeEnum::Specific ? array_values(array_map('intval', (array) ($data['category_ids'] ?? []))) : [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareData(array $data): array
    {
        return [
            'name' => $data['name'],
            'headline' => $data['headline'],
            'type' => $this->offerType($data),
            'description' => ($data['description'] ?? null) ?: null,
            'discount_percentage' => (int) $data['discount_percentage'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'starts_at' => $this->parseDate($data['starts_at'] ?? null)?->startOfDay(),
            'ends_at' => $this->parseDate($data['ends_at'] ?? null)?->endOfDay(),
        ];
    }
}
