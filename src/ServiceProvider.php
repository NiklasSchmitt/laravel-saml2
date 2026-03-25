<?php

namespace NiklasSchmitt\Saml2;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/saml2.php', 'saml2');
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootMiddleware();
        $this->bootRoutes();
        $this->bootPublishes();
        $this->bootCommands();
        $this->loadMigrations();
    }

    /**
     * Bootstrap the routes.
     *
     * @return void
     */
    protected function bootRoutes()
    {
        if (config('saml2.useRoutes', true) == true) {
            include __DIR__ . '/Http/routes.php';
        }
    }

    /**
     * Bootstrap the publishable files.
     *
     * @return void
     */
    protected function bootPublishes()
    {
        $source = __DIR__ . '/../config/saml2.php';

        $this->publishes([$source => config_path('saml2.php')]);
    }

    /**
     * Bootstrap the console commands.
     *
     * @return void
     */
    protected function bootCommands()
    {
        $this->commands([
            Commands\CreateTenant::class,
            Commands\UpdateTenant::class,
            Commands\DeleteTenant::class,
            Commands\RestoreTenant::class,
            Commands\ListTenants::class,
            Commands\TenantCredentials::class,
        ]);
    }

    /**
     * Bootstrap the console commands.
     *
     * @return void
     */
    protected function bootMiddleware()
    {
        $this->app['router']->aliasMiddleware('saml2.resolveTenant', Http\Middleware\ResolveTenant::class);
    }

    /**
     * Load the package migrations.
     *
     * @return void
     */
    protected function loadMigrations()
    {
        if (config('saml2.load_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
