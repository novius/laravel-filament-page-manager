<?php

namespace Novius\LaravelFilamentPageManager\Traits;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;
use Novius\LaravelFilamentPageManager\Facades\PageManager;
use Novius\LaravelFilamentPageManager\Http\Middleware\HandlePages;
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

    protected ?Page $instancePage = null;

    public function addRouteGet(string $subPath, array|string|callable $action, ?string $name = null): ?\Illuminate\Routing\Route
    {
        return $this->addRoute('get', $subPath, $action, $name);
    }

    protected function addRoutePost(string $subPath, array|string|callable $action, ?string $name = null): ?\Illuminate\Routing\Route
    {
        return $this->addRoute('post', $subPath, $action, $name);
    }

    protected function addRoute(string $method, string $subPath, array|string|callable $action, ?string $name = null): ?\Illuminate\Routing\Route
    {
        if ($this->instancePage === null) {
            $this->instancePage = PageManager::model()::getSpecialPage($this, app()->getLocale());
        }
        if ($this->instancePage !== null) {
            return Route::$method($this->instancePage->slug.'/'.ltrim($subPath, '/'), $action)
                ->middleware(HandlePages::class.':'.$this->key())
                ->name($name ?? 'page-manager.'.$this->key().'.'.Str::slug($subPath));
        }

        return null;
    }
}
