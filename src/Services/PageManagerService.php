<?php

namespace Novius\LaravelFilamentPageManager\Services;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;
use LaravelLang\Locales\Data\LocaleData;
use LaravelLang\Locales\Facades\Locales;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;
use Novius\LaravelFilamentPageManager\Contracts\Special;
use Novius\LaravelFilamentPageManager\Http\Middleware\HandlePages;
use Novius\LaravelFilamentPageManager\Models\Page;
use Novius\LaravelMeta\Facades\CurrentModel;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class PageManagerService
{
    public function __construct(protected array $config = []) {}

    /**
     * @return Collection<string, PageTemplate>
     */
    public function templates(): Collection
    {
        $templates = [];
        $potentialTemplates = array_merge(Arr::get($this->config, 'templates', []),
            $this->autoloadIn('autoload_templates_in'));
        foreach ($potentialTemplates as $templateClass) {
            if (! class_exists($templateClass)) {
                continue;
            }

            if (! in_array(PageTemplate::class, class_implements($templateClass), true) ||
                (new ReflectionClass($templateClass))->isAbstract()
            ) {
                continue;
            }
            /** @var PageTemplate $template */
            $template = new $templateClass;
            $templates[$template->key()] = $template;
        }

        return collect($templates);
    }

    public function template(string $templateKey): ?PageTemplate
    {
        $template = $this->templates()->get($templateKey);
        if (empty($template)) {
            return null;
        }

        return $template;
    }

    /**
     * @return Collection<string, Special>
     */
    public function specialPages(): Collection
    {
        $specialPages = [];
        $potentialSpecialPages = array_merge(Arr::get($this->config, 'special', []),
            $this->autoloadIn('autoload_special_in'));
        foreach ($potentialSpecialPages as $specialClass) {
            if (! class_exists($specialClass)) {
                continue;
            }

            if (! in_array(Special::class, class_implements($specialClass), true) ||
                (new ReflectionClass($specialClass))->isAbstract()
            ) {
                continue;
            }
            /** @var Special $special */
            $special = new $specialClass;
            $specialPages[$special->key()] = $special;
        }

        return collect($specialPages);
    }

    public function special(string $specialKey): ?Special
    {
        $special = $this->specialPages()->get($specialKey);
        if (empty($special)) {
            return null;
        }

        return $special;
    }

    /**
     * @return Collection<string, LocaleData>
     */
    public function locales(): Collection
    {
        $locales = Arr::get($this->config, 'locales', []);

        return Locales::installed()
            ->when(! empty($locales), fn (Collection $collection) => $collection->filter(fn (
                LocaleData $localeData
            ) => in_array($localeData->code, $locales, true)));
    }

    public function model(): Page
    {
        $class = config('laravel-filament-page-manager.model', Page::class);

        return new $class;
    }

    public function routes(): void
    {
        try {
            foreach ($this->specialPages() as $specialPage) {
                $specialPage->routes();
            }
        } catch (QueryException $e) {
            report($e);
        }

        Route::get('{page}', fn (Request $request, $page) => $this->render($request, $page))
            ->middleware(HandlePages::class)
            ->where(['page' => config('laravel-filament-page-manager.route_parameter_where', '^((?!admin).)+$')])
            ->name('page-manager.page');
    }

    public function route(Special $special, string $subPath, Closure $routeCallback, ?string $name = null): ?\Illuminate\Routing\Route
    {
        $page = $this->model()::getSpecialPage($special, app()->getLocale());
        if ($page) {
            $route = Route::get($page->slug.'/'.ltrim($subPath, '/'), $routeCallback)
                ->where([
                    'page' => config('laravel-filament-page-manager.route_parameter_where', '^((?!admin).)+$'),
                ])
                ->middleware(HandlePages::class.':'.$special->key())
                ->name($name ?? 'page-manager.'.$special->key().'.'.Str::slug($subPath));
        }

        return $route ?? null;
    }

    public function render(Request $request, ?Page $page = null): View
    {
        if ($page === null) {
            $param = $request->route('page');
            if ($param instanceof Page) {
                $page = $param;
            } else {
                abort(404);
            }
        }
        CurrentModel::setModel($page);

        return view($page->template->view(), $page->template->viewParameters($request, $page));
    }

    protected function autoloadIn($config_key): array
    {
        $namespace = app()->getNamespace();

        $resources = [];
        $autoload_templates_in = Arr::get($this->config, $config_key);
        if (empty($autoload_templates_in) || ! is_dir($autoload_templates_in)) {
            return $resources;
        }

        foreach ((new Finder)->in($this->config[$config_key])->files() as $resource) {
            $resource = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($resource->getPathname(), app_path().DIRECTORY_SEPARATOR)
            );

            $resources[] = $resource;
        }

        return $resources;
    }
}
