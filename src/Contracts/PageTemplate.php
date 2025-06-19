<?php

namespace Novius\LaravelFilamentPageManager\Contracts;

use Filament\Forms\Components\Component;
use Illuminate\Http\Request;
use Novius\LaravelFilamentPageManager\Models\Page;

interface PageTemplate
{
    public function key(): string;

    public function name(): string;

    /** @return array<Component> */
    public function fields(): array;

    public function casts(): array;

    public function view(): string;

    public function viewParameters(Request $request, Page $page): array;
}
