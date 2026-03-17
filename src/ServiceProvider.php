<?php

declare(strict_types=1);

namespace NiklasSchmitt\Saml2;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/saml2.php', 'saml2');
    }

    public function boot(): void
    {
        $this->bootMiddleware();
        $this->bootRoutes();
        $this->bootPublishes();
        $this->bootCommands();
        $this->loadMigrations();
    }

    protected function bootRoutes(): void
    {
        if ((bool) $this->app['config']->get('saml2.useRoutes', true)) {
            $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        }
    }

    protected function bootPublishes(): void
    {
        $source = __DIR__ . '/../config/saml2.php';

        $this->publishes([$source => config_path('saml2.php')]);
    }

    protected function bootCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            \NiklasSchmitt\Saml2\Commands\CreateTenant::class,
            \NiklasSchmitt\Saml2\Commands\UpdateTenant::class,
            \NiklasSchmitt\Saml2\Commands\DeleteTenant::class,
            \NiklasSchmitt\Saml2\Commands\RestoreTenant::class,
            \NiklasSchmitt\Saml2\Commands\ListTenants::class,
            \NiklasSchmitt\Saml2\Commands\TenantCredentials::class
        ]);
    }

    protected function bootMiddleware(): void
    {
        $this->app['router']->aliasMiddleware('saml2.resolveTenant', \NiklasSchmitt\Saml2\Http\Middleware\ResolveTenant::class);
    }

    protected function loadMigrations(): void
    {
        if (config('saml2.load_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }
}
