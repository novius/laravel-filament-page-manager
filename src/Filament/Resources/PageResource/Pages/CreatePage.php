<?php

namespace Novius\LaravelFilamentPageManager\Filament\Resources\PageResource\Pages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LaravelLang\Locales\Facades\Locales;
use Novius\LaravelFilamentPageManager\Filament\PageManagerPlugin;
use Novius\LaravelFilamentPageManager\Models\Page;
use Novius\LaravelFilamentTranslatable\Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    public static function getResource(): string
    {
        return PageManagerPlugin::getPlugin()->getResource();
    }

    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    /**
     * @param  Page  $parent
     */
    protected function getDataFromTranslate(Model $parent, string $locale): array
    {
        $data = $parent->attributesToArray();

        $data['title'] = $parent->title.' '.Locales::get($locale)->native;
        $data['slug'] = Str::slug($data['title']);

        return $data;
    }
}
