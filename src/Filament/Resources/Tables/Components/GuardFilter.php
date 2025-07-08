<?php

namespace Novius\LaravelFilamentPageManager\Filament\Resources\Tables\Components;

use Filament\Tables\Filters\SelectFilter;
use Novius\LaravelFilamentPageManager\Filament\Resources\Concerns\InteractWithGuards;

class GuardFilter extends SelectFilter
{
    use InteractWithGuards;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(trans('laravel-filament-page-manager::messages.guard'));
        $this->hidden(fn () => count($this->getGuards()) < 1);
        $this->options(fn () => collect($this->getGuards())
            ->mapWithKeys(fn (string $guard) => [$guard => $this->getGuardName($guard)])
            ->toArray());
    }
}
