<?php

namespace Novius\LaravelFilamentPageManager\SpecialPages;

use Novius\LaravelFilamentPageManager\Contracts\Special;
use Novius\LaravelFilamentPageManager\Traits\IsSpecialPage;

class Page404 implements Special
{
    use IsSpecialPage;

    public function key(): string
    {
        return '404';
    }

    public function name(): string
    {
        return trans('laravel-filament-page-manager::messages.404');
    }

    public function icon(): string
    {
        return 'heroicon-o-bug-ant';
    }

    public function pageSlug(): ?string
    {
        return '404';
    }

    public function statusCode(): ?int
    {
        return 404;
    }
}
