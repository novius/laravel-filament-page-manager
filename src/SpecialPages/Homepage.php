<?php

namespace Novius\LaravelFilamentPageManager\SpecialPages;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Novius\LaravelFilamentPageManager\Contracts\Special;
use Novius\LaravelFilamentPageManager\Facades\PageManager;
use Novius\LaravelFilamentPageManager\Traits\IsSpecialPage;

class Homepage implements Special
{
    use IsSpecialPage;

    public function key(): string
    {
        return 'homepage';
    }

    public function name(): string
    {
        return trans('laravel-filament-page-manager::messages.homepage');
    }

    public function icon(): string
    {
        return 'heroicon-o-home';
    }

    public function pageSlug(): ?string
    {
        return '/';
    }

    public function routes(): void
    {
        Route::get('/', static fn (Request $request) => PageManager::render($request, PageManager::model()::getHomePage(request: $request)))
            ->name('page-manager.homepage');
    }
}
