<?php

namespace App\Traits\Model;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * @property int|null $updated_by
 * @property CarbonImmutable|null $updated_at
 * @property-read User|null $updatedBy
 *
 * @method static void saving(callable $callback)
 *
 * @mixin Model
 */
trait CmsUpdateTrackerTrait
{
    public static function bootCmsUpdateTrackerTrait(): void
    {
        static::saving(static function (Model $model): void {
            $user = Auth::user();

            if ($user instanceof User && $user->isBackofficeUser()) {
                $model->setAttribute('updated_by', $user->id);
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function lastUpdateLabel(): string
    {
        return ($this->updatedBy->name ?? '-').' · '.formatDateTime($this->updated_at);
    }
}
