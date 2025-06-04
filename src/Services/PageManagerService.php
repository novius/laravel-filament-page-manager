<?php

namespace Novius\LaravelFilamentPageManager\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LaravelLang\Locales\Data\LocaleData;
use LaravelLang\Locales\Facades\Locales;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;
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
        $potentialTemplates = array_merge(Arr::get($this->config, 'templates', []), $this->autoloadTemplates());
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
            $templates[$template->templateUniqueKey()] = $template;
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
     * @return Collection<string, LocaleData>
     */
    public function locales(): Collection
    {
        $locales = Arr::get($this->config, 'locales', []);

        return Locales::installed()
            ->when(! empty($locales), fn (Collection $collection) => $collection->filter(fn (LocaleData $localeData) => in_array($localeData->code, $locales, true)));
    }

    protected function autoloadTemplates(): array
    {
        $namespace = app()->getNamespace();

        $resources = [];
        $autoload_templates_in = Arr::get($this->config, 'autoload_templates_in');
        if (empty($autoload_templates_in) || ! is_dir($autoload_templates_in)) {
            return $resources;
        }

        foreach ((new Finder)->in($this->config['autoload_templates_in'])->files() as $resource) {
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
