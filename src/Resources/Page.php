<?php

namespace Novius\LaravelNovaPageManager\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\BelongsTo;
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
use Novius\LaravelNovaFieldPreview\Nova\Fields\OpenPreview;
use Novius\LaravelNovaPageManager\Helpers\TemplatesHelper;
use Novius\LaravelNovaPublishable\Nova\Fields\ExpiredAt;
use Novius\LaravelNovaPublishable\Nova\Fields\PublicationBadge;
use Novius\LaravelNovaPublishable\Nova\Fields\PublicationStatus as PublicationStatusField;
use Novius\LaravelNovaPublishable\Nova\Fields\PublishedAt;
use Novius\LaravelNovaPublishable\Nova\Fields\PublishedFirstAt;
use Novius\LaravelNovaPublishable\Nova\Filters\PublicationStatus;
use Novius\LaravelNovaTranslatable\Nova\Cards\Locales;
use Novius\LaravelNovaTranslatable\Nova\Fields\Locale;
use Novius\LaravelNovaTranslatable\Nova\Fields\Translations;
use Novius\LaravelNovaTranslatable\Nova\Filters\LocaleFilter;

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

    public static $with = ['translationsWithDeleted'];

    public function availableLocales(): array
    {
        return config('laravel-nova-page-manager.locales', []);
    }

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
            OpenPreview::make(trans('laravel-nova-page-manager::page.preview_link')),

            new Panel(trans('laravel-nova-page-manager::page.panel_main'), $this->mainFields()),

            new Panel(trans('laravel-nova-page-manager::page.panel_seo'), $this->seoFields()),

            new Panel(trans('laravel-nova-page-manager::page.panel_og'), $this->ogFields()),

            ...$templateFields,
        ];
    }

    protected function mainFields(): array
    {
        $templates = TemplatesHelper::getTemplates()->mapWithKeys(function ($template) {
            return [
                $template['template']->templateUniqueKey() => $template['template']->templateName(),
            ];
        })->all();

        return [
            Text::make(trans('laravel-nova-page-manager::page.title'), 'title')
                ->displayUsing(function () {
                    return Str::limit($this->resource->title, self::TITLE_TRUNCATE_LIMIT_CHARS);
                })
                ->rules('required', 'string', 'max:191')
                ->sortable(),

            Slug::make(trans('laravel-nova-menu::menu.slug'), 'slug')
                ->from('title')
                ->sortable()
                ->creationRules('required', 'string', 'max:191', 'pageSlug', 'uniquePage:{{resourceLocale}}')
                ->updateRules('required', 'string', 'max:191', 'pageSlug', 'uniquePage:{{resourceLocale}},{{resourceId}}'),

            Locale::make(),
            Translations::make(),

            BelongsTo::make(trans('laravel-nova-page-manager::page.parent'), 'parent', static::class)
                ->nullable()
                ->withoutTrashed()
                ->searchable()
                ->hideFromIndex(),

            Select::make(trans('laravel-nova-page-manager::page.template'), 'template')
                ->options($templates)
                ->sortable()
                ->rules('required', 'in:'.implode(',', array_keys($templates)))
                ->readonly(fn () => $this->model()->exists),

            PublicationBadge::make(trans('laravel-nova-page-manager::page.publication')),
            PublicationStatusField::make()->onlyOnForms(),
            PublishedFirstAt::make()->hideFromIndex(),
            PublishedAt::make()->onlyOnForms(),
            ExpiredAt::make()->onlyOnForms(),
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
            new Locales(),
        ];
    }

    /**
     * Get the filters available for the resource.
     */
    public function filters(Request $request): array
    {
        return [
            new LocaleFilter(),
            new PublicationStatus(),
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
        return [
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
