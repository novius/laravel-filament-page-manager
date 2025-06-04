<?php

namespace Novius\LaravelFilamentPageManager;

use Illuminate\Support\ServiceProvider;
use Novius\LaravelFilamentPageManager\Console\FrontControllerCommand;
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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-filament-page-manager');

        $this->app->booted(function () {
            Linkable::addModels([Page::class]);
        });

        $packageDir = dirname(__DIR__);

        $this->publishes([$packageDir.'/config' => config_path()], 'config');

        $this->publishes([$packageDir.'/database/migrations' => database_path('migrations')], 'migrations');
        $this->loadMigrationsFrom($packageDir.'/database/migrations');

        $this->loadTranslationsFrom($packageDir.'/lang', 'laravel-filament-page-manager');
        $this->publishes([__DIR__.'/../lang' => lang_path('vendor/laravel-filament-page-manager')], 'lang');

        if ($this->app->runningInConsole()) {
            $this->commands([
                FrontControllerCommand::class,
            ]);
        }
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
