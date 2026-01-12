<?php

namespace Novius\LaravelFilamentPageManager\Database\Seeders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LaravelLang\Locales\Data\LocaleData;
use LaravelLang\Locales\Facades\Locales;
use Mockery\Exception\RuntimeException;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;
use Novius\LaravelFilamentPageManager\Contracts\Special;
use Novius\LaravelFilamentPageManager\Models\Page;
use Novius\LaravelPublishable\Enums\PublicationStatus;

abstract class PageSeeder extends Seeder
{
    /** @var Collection<int, LocaleData>|null */
    protected ?Collection $locales = null;

    /**
     * Run the database seeds.
     *
     * @throws RuntimeException
     */
    public function run(): void
    {
        $pages_config = $this->pages();

        $locales = $this->getLocales();
        foreach ($pages_config as $config) {
            /** @var class-string<Special>|null $special */
            $special = Arr::get($config, 'special');
            if (! empty($special) && ! class_exists($special)) {
                throw new RuntimeException('Special class '.$special.' does not exist');
            }
            if (! empty($special) && ! in_array(Special::class, class_implements($special), true)) {
                throw new RuntimeException('Special class '.$special.' does not implement '.Special::class);
            }

            /** @var class-string<PageTemplate> $template */
            $template = $config['template'];
            if (empty($template)) {
                throw new RuntimeException('template key must be set');
            }
            if (! class_exists($template)) {
                throw new RuntimeException('Template class '.$template.' does not exist');
            }
            if (! in_array(PageTemplate::class, class_implements($template), true)) {
                throw new RuntimeException('Template class '.$template.' does not implement '.PageTemplate::class);
            }

            $guard = Arr::get($config, 'guard');
            if (! empty($guard) && ! array_key_exists($guard, config('auth.guards', []))) {
                throw new RuntimeException('The guard '.$guard.' does not exist in config/auth.guards');
            }

            $title = Arr::get($config, 'title');
            if (empty($title)) {
                throw new RuntimeException('title key must be set');
            }
            $slug = Arr::get($config, 'slug');

            $pageParent = Page::query()
                ->when($special, function (Builder|Page $query) use ($special) {
                    return $query->where('special', (new $special)->key());
                })
                ->first();

            foreach ($locales as $locale) {
                $titleLocalized = $this->getLocalizedString($locale, $title);

                if ($pageParent && $pageParent->locale === $locale->code) {
                    if (! empty($special)) {
                        $pageParent->special = new $special;
                    }
                    if (! empty($guard)) {
                        $pageParent->guard = $guard;
                    }
                    $pageParent->template = new $template;
                    $pageParent->publication_status = PublicationStatus::published;
                    $pageParent->save();

                    $page = $pageParent;
                } else {
                    $page = Page::withLocale($locale->code)->when(
                        $special,
                        function (Builder|Page $query) use ($special) {
                            return $query->where('special', (new $special)->key());
                        },
                        function (Builder|Page $query) use ($titleLocalized) {
                            return $query->where('title', $titleLocalized);
                        })
                        ->first();
                    if ($page === null) {
                        $page = new Page;
                        $page->locale = $locale->code;
                        $page->slug = $slug;
                    }
                    $page->title = $titleLocalized;
                    if (! empty($special)) {
                        $page->special = new $special;
                    }
                    if (! empty($guard)) {
                        $pageParent->guard = $guard;
                    }
                    $page->template = new $template;
                    $page->publication_status = PublicationStatus::published;
                    $page->save();
                }
                /** @var Page $pageParent */
                $pageParent = $pageParent ?? $page;

                $this->postCreate($config, $locale, $page);
            }
        }
    }

    protected function getLocales(): Collection
    {
        if ($this->locales === null) {
            $this->locales = Locales::installed();
        }

        return $this->locales;
    }

    protected function getLocalizedString(LocaleData $locale, ?string $string): ?string
    {
        if (empty($string)) {
            return null;
        }

        return $string.($this->getLocales()->count() > 1 ? ' '.$locale->code : '');
    }

    protected function postCreate(array $config, LocaleData $locale, Page $page): void {}

    /**
     * Example:
     *  [
     *      [
     *          'title' => 'A title',
     *          'slug' => 'a-slug', // optional
     *          'special' => ASpecialPage::class, // optional
     *          'template' => APageTemplateClass::class,
     *      ],
     *  ]
     */
    abstract protected function pages(): array;
}
