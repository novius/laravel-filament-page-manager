<?php

namespace Novius\LaravelFilamentPageManager\Contracts;

use Illuminate\Http\Request;
use Novius\LaravelFilamentPageManager\Models\Page;

interface PageTemplate
{
    public function key(): string;

    public function name(): string;

    /** @return array<\Filament\Schemas\Components\Component> */
    public function fields(): array;

    public function casts(): array;

    /**
     * @phpstan-return view-string
     */
    public function view(): string;

    public function viewParameters(Request $request, Page $page): array;
}
