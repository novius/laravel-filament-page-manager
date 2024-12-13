<?php

namespace Novius\LaravelNovaPageManager\Contracts;

interface PageTemplate
{
    public function templateUniqueKey(): string;

    public function templateName(): string;

    public function fields(): array;

    public function casts(): array;
}
