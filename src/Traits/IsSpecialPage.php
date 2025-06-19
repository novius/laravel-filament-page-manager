<?php

namespace Novius\LaravelFilamentPageManager\Traits;

use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;
use Novius\LaravelFilamentPageManager\Models\Page;

trait IsSpecialPage
{
    public function __construct(protected ?Page $page = null) {}

    public function icon(): ?string
    {
        return null;
    }

    public function pageSlug(): ?string
    {
        return null;
    }

    public function template(): ?PageTemplate
    {
        return null;
    }

    public function statusCode(): ?int
    {
        return null;
    }

    public function routes(): void {}
}
