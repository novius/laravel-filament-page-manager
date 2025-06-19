<?php

namespace Novius\LaravelFilamentPageManager\SpecialPages;

use Novius\LaravelFilamentPageManager\Contracts\Special;

class Page404 implements Special
{
    public function key(): string
    {
        return 'homepage';
    }

    public function name(): string
    {
        return trans('laravel-filament-page-manager::page.homepage');
    }

    public function icon(): string
    {
        return 'heroicon-o-home';
    }

    public function pageSlug(): ?string
    {
        return '/';
    }
}
