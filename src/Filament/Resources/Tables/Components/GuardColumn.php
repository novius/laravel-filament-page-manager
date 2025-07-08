<?php

namespace Novius\LaravelFilamentPageManager\Filament\Resources\Tables\Components;

use Filament\Tables\Columns\TextColumn;
use Novius\LaravelFilamentPageManager\Filament\Resources\Concerns\InteractWithGuards;

class GuardColumn extends TextColumn
{
    use InteractWithGuards;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(trans('laravel-filament-page-manager::messages.guard'));
        $this->hidden(fn () => count($this->getGuards()) < 1);
        $this->formatStateUsing(fn (?string $state) => $this->getGuards() > 1 ? $this->getGuardName($state) : '');
        $this->icon(fn (?string $state) => $this->getGuardIcon($state));
    }
}
