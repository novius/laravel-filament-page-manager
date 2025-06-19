<?php

namespace Novius\LaravelFilamentPageManager\Templates;

use Filament\Forms\Components\RichEditor;
use Illuminate\Http\Request;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;
use Novius\LaravelFilamentPageManager\Models\Page;

class DefaultTemplate implements PageTemplate
{
    public function name(): string
    {
        return trans('laravel-filament-page-manager::messages.default_template');
    }

    public function key(): string
    {
        return 'default';
    }

    public function fields(): array
    {
        return [
            RichEditor::make('content')
                ->label(trans('laravel-filament-page-manager::messages.content')),
        ];
    }

    public function casts(): array
    {
        return [];
    }

    public function view(): string
    {
        return 'laravel-filament-page-manager::default';
    }

    public function viewParameters(Request $request, Page $page): array
    {
        return ['page' => $page];
    }
}
