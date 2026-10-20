<?php

namespace App\Providers;

use App\Enums\User\PermissionEnum;
use App\Enums\User\UserRoleEnum;
use App\Models\User;
use App\Services\Backoffice\System\MaintenanceModeService;
use App\Services\Backoffice\System\SystemSettingService;
use App\Services\Frontend\AiAssistant\AiAssistantSearchService;
use App\Traits\Cache\ClearsFrontendCacheTrait;
use Carbon\CarbonImmutable;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\DevCommands;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\MaintenanceModeManager;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View as ViewInstance;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(AiAssistantSearchService::class);
        $this->app->scoped(SystemSettingService::class);
        $this->app->afterResolving(MaintenanceModeManager::class, fn (MaintenanceModeManager $manager): MaintenanceModeManager => $manager->extend('system', fn (Application $app): MaintenanceModeService => $app->make(MaintenanceModeService::class)));
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAdminAuthorization();
        $this->configurePasswordResetUrl();
        $this->configureEmailVerificationUrl();
        $this->configureRoleUserProvider();
        $this->configureDevServer();
        $this->configureBranding();
        $this->configureFrontendCacheClearing();
        $this->configureAdminRoutes();
    }

    private function configureAdminRoutes(): void
    {
        PreventRequestsDuringMaintenance::except(adminRoutePrefix().'/*');
    }

    private function configureFrontendCacheClearing(): void
    {
        $clear = static function (Media $media): void {
            $class = Relation::getMorphedModel($media->model_type) ?? $media->model_type;

            if (in_array(ClearsFrontendCacheTrait::class, class_uses_recursive($class), true) && method_exists($model = new $class, 'clearFrontendCache')) {
                $model->clearFrontendCache();
            }
        };

        Media::saved($clear);
        Media::deleted($clear);
    }

    private function configureBranding(): void
    {
        View::composer(['partials.head', 'components.brand.app-logo', 'components.brand.app-logo-icon'], fn (ViewInstance $view): ViewInstance => $view->with('brandUrls', app(SystemSettingService::class)->brandUrls()));
        View::composer('errors::503', fn (ViewInstance $view): ViewInstance => $view->with('maintenanceEndsAt', app(SystemSettingService::class)->maintenanceEndsAt()));
    }

    private function configureDevServer(): void
    {
        DevCommands::artisan('serve --host='.appServerHost().' --port='.appServerPort(), 'server');
    }

    private function configureRoleUserProvider(): void
    {
        Auth::provider('role', fn (Application $app, array $config): EloquentUserProvider => (new EloquentUserProvider($app->make(Hasher::class), $config['model']))
            ->withQuery(fn (Builder $query): Builder => $query->{$config['admin'] ? 'whereHas' : 'whereDoesntHave'}(
                'roles',
                fn (Builder $roles): Builder => $roles->where('name', '!=', UserRoleEnum::User->value),
            )));
    }

    private function configureAdminAuthorization(): void
    {
        Gate::before(static fn (User $user, string $ability): ?bool => $user->isSuperAdmin() && PermissionEnum::tryFrom($ability) !== null ? true : null);
    }

    private function configurePasswordResetUrl(): void
    {
        ResetPassword::createUrlUsing(static fn (User $user, string $token): string => route(
            $user->isBackofficeUser() ? 'password.reset' : 'user.password.reset',
            ['token' => $token, 'email' => $user->getEmailForPasswordReset()],
        ));
    }

    private function configureEmailVerificationUrl(): void
    {
        VerifyEmail::createUrlUsing(static fn (User $user): string => URL::temporarySignedRoute(
            'user.verification.verify',
            now()->addMinutes(emailVerificationExpireMinutes()),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
        ));

        VerifyEmail::toMailUsing(static fn (User $user, string $url): MailMessage => (new MailMessage)
            ->subject(__('Verify your email address'))
            ->line(__('Please click the button below to verify your email address.'))
            ->action(__('Verify email address'), $url)
            ->line(__('This link works for :minutes minutes. If it expires, log in again to get a new one.', ['minutes' => emailVerificationExpireMinutes()]))
            ->line(__('If you did not create an account, no further action is required.')));
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Model::preventLazyLoading(! app()->isProduction());

        Password::defaults(
            fn (): Password => app()->isProduction()
                ? Password::min(passwordMinLength())
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : Password::min(passwordMinLength()),
        );
    }
}
