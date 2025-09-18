<?php

namespace Novius\LaravelFilamentPageManager\StateCasts;

use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Novius\LaravelFilamentPageManager\Contracts\Special;
use Novius\LaravelFilamentPageManager\Facades\PageManager;

class SpecialPageStateCast implements StateCast
{
    public function get(mixed $state): ?Special
    {
        if (is_null($state)) {
            return null;
        }

        return PageManager::special($state);
    }

    public function set(mixed $state): mixed
    {
        if ($state instanceof Special) {
            return $state->key();
        }

        return $state;
    }
}
