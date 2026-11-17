<?php

namespace Novius\LaravelFilamentPageManager\Tests;

use Novius\LaravelFilamentPageManager\LaravelFilamentPageManagerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Discover the providers of the dependencies (Filament, Linkable, Meta...), like a real application.
     */
    public function ignorePackageDiscoveriesFrom(): array
    {
        return [];
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelFilamentPageManagerServiceProvider::class,
        ];
    }
}
