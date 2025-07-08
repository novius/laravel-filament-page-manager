<?php

namespace Novius\LaravelFilamentPageManager\Facades;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\View;
use LaravelLang\Locales\Data\LocaleData;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;
use Novius\LaravelFilamentPageManager\Contracts\Special;
use Novius\LaravelFilamentPageManager\Models\Page;
use Novius\LaravelFilamentPageManager\Services\PageManagerService;

/**
 * @method static Collection<string, PageTemplate> templates()
 * @method static PageTemplate|null template(string $templateKey)
 * @method static Collection<string, Special> specialPages()
 * @method static Special|null special(string $specialKey)
 * @method static Collection<string, LocaleData> locales()
 * @method static View render(Request $request, ?Page $page = null)
 * @method static void routes()
 * @method static Route|null route(Special $special, string $subPath, Closure $routeCallback, ?string $name = null)
 *
 * @see PageManagerService
 */
class PageManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PageManagerService::class;
    }
}
