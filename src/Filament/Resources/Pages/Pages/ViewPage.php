<?php

namespace Novius\LaravelFilamentPageManager\Filament\Resources\Pages\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Novius\LaravelFilamentActionPreview\Filament\Actions\PreviewAction;
use Novius\LaravelFilamentPageManager\Filament\PageManagerPlugin;

class ViewPage extends ViewRecord
{
    public static function getResource(): string
    {
        return PageManagerPlugin::getPlugin()->getResource();
    }

    protected function getHeaderActions(): array
    {
        return [
            PreviewAction::make(),
            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
