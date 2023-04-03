<?php

namespace Novius\LaravelNovaPageManager\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Laravel\Nova\Resource;
use Novius\LaravelNovaContexts\Fields\ContextField;
use Novius\LaravelNovaContexts\Filters\ContextFilter;
use Novius\LaravelNovaContexts\LaravelNovaContexts;
use Novius\LaravelNovaPageManager\Actions\TranslatePage;
use Novius\LaravelNovaPageManager\Filters\PublishedFilter;
use Novius\LaravelNovaPageManager\Helpers\TemplatesHelper;

class Page extends Resource
{
    public const TITLE_TRUNCATE_LIMIT_CHARS = 25;

    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \Novius\LaravelNovaPageManager\Models\Page::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'title';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = ['title'];

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(Request $request): array
    {
        $currentTemplateName = $this->resource->template;
        $templateFields = [];
        if ($this->model()->exists) {
            $template = TemplatesHelper::getTemplate($currentTemplateName, $this);
            if (null !== $template) {
                $templateFields = $this->normalizeTemplateFields($template->templateName(), $template->fields());
            }
        }

        return [
            ID::make(__('ID'), 'id')->sortable(),

            new Panel(trans('laravel-nova-page-manager::page.panel_main'), $this->mainFields()),

            new Panel(trans('laravel-nova-page-manager::page.panel_seo'), $this->seoFields()),

            new Panel(trans('laravel-nova-page-manager::page.panel_og'), $this->ogFields()),

            ...$templateFields,
        ];
    }

    protected function mainFields(): array
    {
        $locales = config('laravel-nova-page-manager.locales', []);
        $templates = TemplatesHelper::getTemplates()->mapWithKeys(function ($template) {
            return [
                $template['template']->templateUniqueKey() => $template['template']->templateName(),
            ];
        })->all();

        return [
            Text::make(trans('laravel-nova-page-manager::page.title'), function () {
                $previewUrl = $this->resource->previewUrl();
                if (empty($previewUrl)) {
                    return Str::limit($this->resource->title, self::TITLE_TRUNCATE_LIMIT_CHARS);
                }

                return sprintf(
                    '<a class="link-default" href="%s" target="_blank" title="%s">%s</a>',
                    $previewUrl,
                    e($this->resource->title),
                    e(Str::limit($this->resource->title, self::TITLE_TRUNCATE_LIMIT_CHARS))
                );
            })
                ->asHtml()
                ->onlyOnIndex(),

            Text::make(trans('laravel-nova-page-manager::page.title'), 'title')
                ->rules('required', 'string', 'max:191')
                ->sortable()
                ->hideFromIndex(),

            Slug::make(trans('laravel-nova-menu::menu.slug'), 'slug')
                ->from('title')
                ->creationRules('required', 'string', 'max:191', 'pageSlug', 'uniquePage:{{resourceLocale}}')
                ->updateRules('required', 'string', 'max:191', 'pageSlug', 'uniquePage:{{resourceLocale}},{{resourceId}}'),

            ContextField::make(trans('laravel-nova-page-manager::page.locale'), 'locale')
                ->rules('in:'.implode(',', array_keys($locales)))
                ->hideFromIndex(function () use ($locales) {
                    return count($locales) < 2;
                })
                ->hideWhenCreating(function () use ($locales) {
                    return count($locales) < 2;
                })
                ->hideWhenUpdating(function () use ($locales) {
                    return count($locales) < 2;
                })
                ->hideFromDetail(function () use ($locales) {
                    return count($locales) < 2;
                }),

            BelongsTo::make(trans('laravel-nova-page-manager::page.parent'), 'parent', static::class)
                ->nullable()
                ->withoutTrashed()
                ->searchable()
                ->hideFromIndex(),

            Select::make(trans('laravel-nova-page-manager::page.template'), 'template')
                ->options($templates)
                ->rules('required', 'in:'.implode(',', array_keys($templates)))
                ->readonly(fn () => $this->model()->exists),

            Boolean::make(trans('laravel-nova-page-manager::page.is_published'), function () {
                return $this->resource->isPublished();
            })->exceptOnForms(),

            DateTime::make(trans('laravel-nova-page-manager::page.publication_date'), 'publication_date')
                ->nullable()
                ->rules('nullable', 'date'),

            DateTime::make(trans('laravel-nova-page-manager::page.publication_end_date'), 'end_publication_date')
                ->nullable()
                ->rules('nullable', 'after:publication_date'),

        ];
    }

    protected function seoFields(): array
    {
        return [
            Text::make(trans('laravel-nova-page-manager::page.seo_title'), 'seo_title')
                ->rules('required', 'string', 'max:191')
                ->hideFromIndex(),

            Textarea::make(trans('laravel-nova-page-manager::page.seo_description'), 'seo_description')
                ->rules('required', 'string', 'max:191')
                ->hideFromIndex(),

            Select::make(trans('laravel-nova-page-manager::page.seo_robots'), 'seo_robots')
                ->rules('required', 'in:'.implode(',', array_keys($this->model()->robotsDirectives())))
                ->options(
                    $this->model()->robotsDirectives()
                )
                ->resolveUsing(fn ($value) => $value ?? \Novius\LaravelNovaPageManager\Models\Page::ROBOTS_INDEX_FOLLOW)
                ->help(trans('laravel-nova-page-manager::page.seo_robots_default_help'))
                ->displayUsing((fn ($value) => \Novius\LaravelNovaPageManager\Models\Page::findRobotDirective($value)['value_for_robots'] ?? $value))
                ->hideFromIndex(),

            Text::make(trans('laravel-nova-page-manager::page.seo_canonical_url'), 'seo_canonical_url')
                ->rules('nullable', 'string', 'url', 'max:191')
                ->hideFromIndex(),
        ];
    }

    protected function ogFields(): array
    {
        return [
            Text::make(trans('laravel-nova-page-manager::page.og_title'), 'og_title')
                ->rules('nullable', 'string', 'max:191')
                ->hideFromIndex(),

            Textarea::make(trans('laravel-nova-page-manager::page.og_description'), 'og_description')
                ->rules('nullable', 'string', 'max:191')
                ->hideFromIndex(),

            Image::make(trans('laravel-nova-page-manager::page.og_image'), 'og_image')
                ->maxWidth(500)
                ->prunable()
                ->store(function (Request $request, $model) {
                    return [
                        'og_image' => $request->og_image->store(
                            config('laravel-nova-page-manager.og_image_path', '/'),
                            config('laravel-nova-page-manager.og_image_disk', 'public')
                        ),
                    ];
                })
                ->hideFromIndex(),
        ];
    }

    protected function normalizeTemplateFields(string $templateName, array $templateFields): array
    {
        $fieldsWithoutPanel = [];
        foreach ($templateFields as $key => &$field) {
            if ($field instanceof Heading) {
                $field->hideFromDetail();
                $fieldsWithoutPanel[] = $field;
                unset($templateFields[$key]);

                continue;
            }

            if ($field instanceof Field) {
                if ($field->attribute !== 'ComputedField') {
                    $field->attribute = 'extras->'.$field->attribute;
                }
                $field->hideFromIndex();
                $fieldsWithoutPanel[] = $field;
                unset($templateFields[$key]);
            }

            if ($field instanceof Panel) {
                foreach ($field->data as &$panelField) {
                    $panelField->attribute = 'extras->'.$panelField->attribute;
                }
            }
        }

        $fields = [];
        if (! empty($fieldsWithoutPanel)) {
            $fields['default_panel'] = Panel::make($templateName, $fieldsWithoutPanel);
        }

        $fields += $templateFields;

        return array_values($fields);
    }

    /**
     * Get the cards available for the request.
     */
    public function cards(Request $request): array
    {
        return [
            (new LaravelNovaContexts())->dynamicHeight(),
        ];
    }

    /**
     * Get the filters available for the resource.
     */
    public function filters(Request $request): array
    {
        return [
            new ContextFilter($this->model()),
            new PublishedFilter(),
        ];
    }

    /**
     * Get the lenses available for the resource.
     */
    public function lenses(Request $request): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     */
    public function actions(Request $request): array
    {
        $locales = config('laravel-nova-page-manager.locales', []);
        if (count($locales) <= 1) {
            return [];
        }

        return [
            (new TranslatePage())->onlyInline(),
        ];
    }

    /**
     * Perform any final formatting of the given validation rules.
     */
    protected static function formatRules(NovaRequest $request, array $rules): array
    {
        $locales = config('laravel-nova-page-manager.locales', []);
        $locale = (count($locales) === 1) ? array_key_first($locales) : $request->get('locale', '');

        $replacements = array_filter([
            '{{resourceId}}' => str_replace(['\'', '"', ',', '\\'], '', $request->resourceId),
            '{{resourceLocale}}' => str_replace(['\'', '"', ',', '\\'], '', $locale),
        ]);

        if (empty($replacements)) {
            return $rules;
        }

        return collect($rules)->map(function ($rules) use ($replacements) {
            return collect($rules)->map(function ($rule) use ($replacements) {
                return is_string($rule)
                    ? str_replace(array_keys($replacements), array_values($replacements), $rule)
                    : $rule;
            })->all();
        })->all();
    }
}
