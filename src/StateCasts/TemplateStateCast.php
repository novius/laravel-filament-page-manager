<?php

namespace Novius\LaravelFilamentPageManager\StateCasts;

use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;
use Novius\LaravelFilamentPageManager\Facades\PageManager;

class TemplateStateCast implements StateCast
{
    public function get(mixed $state): ?PageTemplate
    {
        if (is_null($state)) {
            return null;
        }

        return PageManager::template($state);
    }

    public function set(mixed $state): mixed
    {
        if ($state instanceof PageTemplate) {
            return $state->key();
        }

        return $state;
    }
}
