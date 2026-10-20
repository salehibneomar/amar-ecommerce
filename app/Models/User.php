<?php

namespace App\Models;

use App\Enums\User\UserRoleEnum;
use App\Enums\User\UserStatusEnum;
use App\Traits\Model\MediaTrait;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserStatusEnum $status
 * @property CarbonImmutable|null $email_verified_at
 * @property string|null $password
 * @property string|null $google_id
 * @property string|null $facebook_id
 * @property string|null $phone
 * @property string|null $shipping_address
 * @property string|null $billing_address
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Role> $roles
 * @property-read string|null $avatar_url
 * @property-read int|null $orders_count
 */
#[Fillable(['name', 'email', 'password', 'status', 'google_id', 'facebook_id', 'phone', 'shipping_address', 'billing_address'])]
#[
    Hidden([
        'password',
        'google_id',
        'facebook_id',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ]),
]
class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, InteractsWithMedia, MediaTrait, Notifiable, SoftDeletes {
        MediaTrait::registerMediaCollections insteadof InteractsWithMedia;
        MediaTrait::registerMediaConversions insteadof InteractsWithMedia;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatusEnum::class,
        ];
    }

    /**
     * @return list<string>
     */
    public function mediaConfigPaths(): array
    {
        return [
            'user.media.avatar',
        ];
    }

    /**
     * @return Attribute<never, string|null>
     */
    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => filled($value) ? trim($value) : null);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->mediaUrl('user.media.avatar', 'medium'));
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRoleEnum::SuperAdmin->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRoleEnum::Admin->value);
    }

    public function isBackofficeUser(): bool
    {
        return $this->getRoleNames()->contains(fn (string $role): bool => $role !== UserRoleEnum::User->value);
    }

    /**
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function staff(Builder $query): void
    {
        $query->whereHas('roles', fn (Builder $roles): Builder => $roles->where('name', '!=', UserRoleEnum::User->value));
    }

    /**
     * @param  Builder<User>  $query
     */
    #[Scope]
    protected function customers(Builder $query): void
    {
        $query->whereDoesntHave('roles', fn (Builder $roles): Builder => $roles->where('name', '!=', UserRoleEnum::User->value));
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasOne<StaffProfile, $this>
     */
    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    /**
     * @return HasMany<StaffPayment, $this>
     */
    public function staffPayments(): HasMany
    {
        return $this->hasMany(StaffPayment::class);
    }

    public function isBlocked(): bool
    {
        return $this->status === UserStatusEnum::Blocked;
    }

    public function hasPassword(): bool
    {
        return filled($this->password);
    }

    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
