<?php

namespace Novius\LaravelFilamentPageManager\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use LaravelLang\Locales\Data\LocaleData;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;
use Novius\LaravelFilamentPageManager\Services\PageManagerService;

/**
 * @method static Collection<string, PageTemplate> templates(?Resource $resource = null)
 * @method static PageTemplate|null template(string $templateKey, ?Resource $resource = null):
 * @method static Collection<string, LocaleData> locales()
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
