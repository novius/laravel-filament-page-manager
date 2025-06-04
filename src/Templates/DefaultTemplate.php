<?php

namespace Novius\LaravelFilamentPageManager\Templates;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;

class DefaultTemplate implements PageTemplate
{
    public function templateName(): string
    {
        return trans('laravel-filament-page-manager::template.default_template');
    }

    public function templateUniqueKey(): string
    {
        return 'default';
    }

    public function fields(): array
    {
        return [
            TextInput::make('title')
                ->label(trans('laravel-filament-page-manager::template.field_title')),

            TextInput::make('subtitle')
                ->label(trans('laravel-filament-page-manager::template.field_subtitle')),

            RichEditor::make('content')
                ->label(trans('laravel-filament-page-manager::template.field_content')),
        ];
    }

    public function casts(): array
    {
        return [];
    }
}
