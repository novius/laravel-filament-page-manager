<?php

namespace Novius\LaravelFilamentPageManager\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Resources\Resource;
use InvalidArgumentException;
use Novius\LaravelFilamentPageManager\Filament\Resources\Pages\PageResource;

class PageManagerPlugin implements Plugin
{
    /** @var class-string<resource> */
    protected string $pageResource;

    public function __construct()
    {
        $this->pageResource = config('laravel-filament-page-manager.filamentResource', PageResource::class);
        if (! is_subclass_of($this->pageResource, Resource::class)) {
            throw new InvalidArgumentException('The page resource must be a subclass of '.Resource::class);
        }
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'laravel-filament-page-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            config('laravel-filament-page-manager.filamentResource', PageResource::class),
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function getPlugin(): PageManagerPlugin
    {
        /** @phpstan-ignore return.type */
        return filament('laravel-filament-page-manager');
    }

    public function getResource(): string
    {
        return $this->pageResource;
    }
}
