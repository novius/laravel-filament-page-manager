<?php

namespace Novius\LaravelNovaPageManager;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Nova;
use Novius\LaravelNovaPageManager\Console\FrontControllerCommand;
use Novius\LaravelNovaPageManager\Models\Page;

class LaravelNovaPageManagerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-nova-page-manager');

        $this->app->booted(function () {
            Nova::resources(config('laravel-nova-page-manager.resources', []));
        });

        $packageDir = dirname(__DIR__);

        $this->publishes([$packageDir.'/config' => config_path()], 'config');

        $this->publishes([$packageDir.'/database/migrations' => database_path('migrations')], 'migrations');
        $this->loadMigrationsFrom($packageDir.'/database/migrations');

        $this->loadTranslationsFrom($packageDir.'/lang', 'laravel-nova-page-manager');
        $this->publishes([__DIR__.'/../lang' => lang_path('vendor/laravel-nova-page-manager')], 'lang');

        if ($this->app->runningInConsole()) {
            $this->commands([
                FrontControllerCommand::class,
            ]);
        }

        Validator::extend('pageSlug', function ($attr, $value) {
            return is_string($value) && preg_match('/^[a-zA-Z0-9-_]+$/', $value);
        });

        Validator::extend('uniquePage', function ($attr, $value, $parameters) {
            if (empty($parameters[0])) {
                return false;
            }

            $resourceId = $parameters[1] ?? null;
            $query = Page::where('locale', $parameters[0])
                ->where('slug', $value);
            if ($resourceId) {
                $query->where('id', '<>', $resourceId);
            }

            return empty($query->first());
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravel-nova-page-manager.php',
            'laravel-nova-page-manager'
        );
    }
}
