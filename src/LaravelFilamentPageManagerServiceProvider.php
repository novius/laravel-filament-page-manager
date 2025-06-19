<?php

namespace Novius\LaravelFilamentPageManager;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Novius\LaravelFilamentPageManager\Models\Page;
use Novius\LaravelFilamentPageManager\Services\PageManagerService;
use Novius\LaravelLinkable\Facades\Linkable;

class LaravelFilamentPageManagerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->booted(function () {
            Linkable::addModels([Page::class]);
        });

        $packageDir = dirname(__DIR__);

        $this->publishes([$packageDir.'/config' => config_path()], 'config');

        $this->publishes([$packageDir.'/database/migrations' => database_path('migrations')], 'migrations');
        $this->loadMigrationsFrom($packageDir.'/database/migrations');

        $this->loadTranslationsFrom($packageDir.'/lang', 'laravel-filament-page-manager');
        $this->publishes([__DIR__.'/../lang' => lang_path('vendor/laravel-filament-page-manager')], 'lang');

        $this->loadViewsFrom($packageDir.'/resources/views', 'laravel-filament-page-manager');

        Route::model('page', config('laravel-filament-page-manager.model', Page::class));
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PageManagerService::class, static function () {
            return new PageManagerService(config('laravel-filament-page-manager'));
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/laravel-filament-page-manager.php',
            'laravel-filament-page-manager'
        );
    }
}
