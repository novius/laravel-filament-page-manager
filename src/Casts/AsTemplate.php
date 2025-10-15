<?php

namespace Novius\LaravelFilamentPageManager\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;
use Novius\LaravelFilamentPageManager\Facades\PageManager;

class AsTemplate implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PageTemplate
    {
        if (is_null($value)) {
            return null;
        }

        return PageManager::template($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        if ($value instanceof PageTemplate) {
            return $value->key();
        }

        return $value;
    }
}
