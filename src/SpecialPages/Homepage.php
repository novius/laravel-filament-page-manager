<?php

namespace Novius\LaravelFilamentPageManager\Special;

use Novius\LaravelFilamentPageManager\Contracts\Special;

class Homepage implements Special
{
    public function key(): string
    {
        return 'homepage';
    }

    public function name(): string
    {
        return trans('laravel-filament-page-manager::page.homepage');
    }
}
