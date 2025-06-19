<?php

namespace Novius\LaravelFilamentPageManager\Contracts;

interface Special
{
    public function key(): string;

    public function name(): string;

    public function icon(): ?string;

    public function pageSlug(): ?string;

    public function template(): ?PageTemplate;

    public function statusCode(): ?int;

    public function routes(): void;
}
