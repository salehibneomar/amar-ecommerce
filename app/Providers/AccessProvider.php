<?php

namespace App\Providers;

use App\Enums\User\PermissionEnum;
use App\Enums\User\UserRoleEnum;
use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AccessProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->grantSuperAdminPermissions();
        $this->registerRoleUserProvider();
    }

    private function grantSuperAdminPermissions(): void
    {
        Gate::before(static fn (User $user, string $ability): ?bool => $user->isSuperAdmin() && PermissionEnum::tryFrom($ability) !== null ? true : null);
    }

    private function registerRoleUserProvider(): void
    {
        Auth::provider('role', fn (Application $app, array $config): EloquentUserProvider => (new EloquentUserProvider($app->make(Hasher::class), $config['model']))
            ->withQuery(fn (Builder $query): Builder => $query->{$config['admin'] ? 'whereHas' : 'whereDoesntHave'}(
                'roles',
                fn (Builder $roles): Builder => $roles->where('name', '!=', UserRoleEnum::User->value),
            )));
    }
}
