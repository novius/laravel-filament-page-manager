<?php

namespace Novius\LaravelFilamentPageManager\Filament\Resources\Pages\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Novius\LaravelFilamentActionPreview\Filament\Actions\PreviewAction;
use Novius\LaravelFilamentPageManager\Filament\PageManagerPlugin;
use Novius\LaravelFilamentPageManager\Models\Page;

class EditPage extends EditRecord
{
    public static function getResource(): string
    {
        return PageManagerPlugin::getPlugin()->getResource();
    }

    protected function getHeaderActions(): array
    {
        return [
            PreviewAction::make(),
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  Page  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['extras'] = array_merge(
            $record->extras->toArray(),
            $data['extras'] ?? []
        );

        return parent::handleRecordUpdate($record, $data);
    }
}
