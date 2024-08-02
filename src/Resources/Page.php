<?php

namespace Novius\LaravelNovaPageManager\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Slug;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Laravel\Nova\Resource;
use Novius\LaravelMeta\Traits\NovaResourceHasMeta;
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
    use NovaResourceHasMeta;

    public const TITLE_TRUNCATE_LIMIT_CHARS = 25;

    /**
     * The model the resource corresponds to.
     *
     * @var class-string<\Novius\LaravelNovaPageManager\Models\Page>
     */
    public static string $model = \Novius\LaravelNovaPageManager\Models\Page::class;

    /** @var \Novius\LaravelNovaPageManager\Models\Page|null */
    public $resource;

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
            if ($template !== null) {
                $templateFields = $this->normalizeTemplateFields($template->templateName(), $template->fields());
            }
        }

        return [
            ID::make(__('ID'), 'id')->sortable(),
            OpenPreview::make(trans('laravel-nova-page-manager::page.preview_link')),

            new Panel(trans('laravel-nova-page-manager::page.panel_main'), $this->mainFields()),

            new Panel(
                trans('laravel-nova-page-manager::page.panel_seo'),
                $this->getSEONovaFields()
                    ->prepend(Text::make(trans('laravel-nova-page-manager::page.seo_canonical_url'), 'meta->seo_canonical_url')
                        ->rules('nullable', 'string', 'url', 'max:191')
                        ->hideFromIndex())
            ),

            ...$templateFields,
        ];
    }

    protected function mainFields(): array
    {
        $templates = TemplatesHelper::getTemplates()->mapWithKeys(fn ($template) => [
            $template['template']->templateUniqueKey() => $template['template']->templateName(),
        ])->all();

        return [
            Text::make(trans('laravel-nova-page-manager::page.title'), 'title')
                ->displayUsing(function () {
                    return Str::limit($this->resource->title, self::TITLE_TRUNCATE_LIMIT_CHARS);
                })
                ->rules('required', 'string', 'max:191')
                ->sortable(),

            Slug::make(trans('laravel-nova-page-manager::page.slug'), 'slug')
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
                foreach ($field->data as $panelField) {
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
            new Locales,
        ];
    }

    /**
     * Get the filters available for the resource.
     */
    public function filters(Request $request): array
    {
        return [
            new LocaleFilter,
            new PublicationStatus,
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

        return collect($rules)
            ->map(
                fn ($rules) => collect($rules)->map(
                    fn ($rule) => is_string($rule) ?
                        str_replace(array_keys($replacements), array_values($replacements), $rule) :
                        $rule
                )->all()
            )->all();
    }
}
