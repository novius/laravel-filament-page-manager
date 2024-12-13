<?php

namespace Novius\LaravelNovaPageManager\Templates;

use Laravel\Nova\Resource;
use Novius\LaravelNovaPageManager\Contracts\PageTemplate;

abstract class AbstractPageTemplate implements PageTemplate
{
    protected ?Resource $resource = null;

    public function __construct(?Resource $resource = null)
    {
        $this->resource = $resource;
    }

    public function casts(): array
    {
        return [];
    }
}
