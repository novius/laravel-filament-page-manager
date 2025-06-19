<?php

namespace Novius\LaravelFilamentPageManager\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Novius\LaravelFilamentPageManager\Contracts\Special;
use Novius\LaravelFilamentPageManager\Facades\PageManager;

class AsSpecialPage implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Special
    {
        if (is_null($value)) {
            return null;
        }

        return PageManager::special($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        if ($value instanceof Special) {
            return $value->key();
        }

        return $value;
    }
}
