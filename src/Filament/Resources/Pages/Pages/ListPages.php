<?php

namespace Novius\LaravelFilamentPageManager\Filament\Resources\Pages\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Novius\LaravelFilamentPageManager\Filament\PageManagerPlugin;

class ListPages extends ListRecords
{
    public static function getResource(): string
    {
        return PageManagerPlugin::getPlugin()->getResource();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
