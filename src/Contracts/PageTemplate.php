<?php

namespace Novius\LaravelFilamentPageManager\Contracts;

use Filament\Forms\Components\Component;

interface PageTemplate
{
    public function templateUniqueKey(): string;

    public function templateName(): string;

    /** @return array<Component> */
    public function fields(): array;

    public function casts(): array;
}
