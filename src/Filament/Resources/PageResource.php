<?php

namespace Novius\LaravelFilamentPageManager\Filament\Resources;

use Exception;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use Novius\LaravelFilamentActionPreview\Filament\Tables\Actions\PreviewAction;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;
use Novius\LaravelFilamentPageManager\Contracts\Special;
use Novius\LaravelFilamentPageManager\Facades\PageManager;
use Novius\LaravelFilamentPageManager\Filament\Resources\Forms\Components\SelectGuard;
use Novius\LaravelFilamentPageManager\Filament\Resources\PageResource\Pages;
use Novius\LaravelFilamentPageManager\Filament\Resources\Tables\Components\GuardColumn;
use Novius\LaravelFilamentPageManager\Filament\Resources\Tables\Components\GuardFilter;
use Novius\LaravelFilamentPageManager\Models\Page;
use Novius\LaravelFilamentPublishable\Filament\Forms\Components\ExpiredAt;
use Novius\LaravelFilamentPublishable\Filament\Forms\Components\PublicationStatus;
use Novius\LaravelFilamentPublishable\Filament\Forms\Components\PublishedAt;
use Novius\LaravelFilamentPublishable\Filament\Forms\Components\PublishedFirstAt;
use Novius\LaravelFilamentPublishable\Filament\Tables\Actions\PublicationBulkAction;
use Novius\LaravelFilamentPublishable\Filament\Tables\Columns\PublicationColumn;
use Novius\LaravelFilamentPublishable\Filament\Tables\Filters\PublicationStatusFilter;
use Novius\LaravelFilamentSlug\Filament\Forms\Components\Slug;
use Novius\LaravelFilamentTranslatable\Filament\Forms\Components\Locale;
use Novius\LaravelFilamentTranslatable\Filament\Tables\Columns\LocaleColumn;
use Novius\LaravelFilamentTranslatable\Filament\Tables\Columns\TranslationsColumn;
use Novius\LaravelFilamentTranslatable\Filament\Tables\Filters\LocaleFilter;
use Novius\LaravelMeta\Traits\FilamentResourceHasMeta;

class PageResource extends Resource
{
    use FilamentResourceHasMeta;

    protected static ?string $slug = 'pages';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $recordRouteKeyName = 'id';

    public static function getModel(): string
    {
        return config('laravel-filament-page-manager.model', Page::class);
    }

    public static function getModelLabel(): string
    {
        return trans('laravel-filament-page-manager::messages.modelLabel');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('laravel-filament-page-manager::messages.modelsLabel');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('main')
                            ->label(trans('laravel-filament-page-manager::messages.panel_main'))
                            ->schema(static::tabMain()),
                        Tabs\Tab::make('seo')
                            ->label(trans('laravel-filament-page-manager::messages.panel_seo'))
                            ->schema(static::tabSeo()),
                        static::templateTab(),
                    ])
                    ->columns()
                    ->persistTabInQueryString(),
            ]);
    }

    protected static function tabMain(): array
    {
        return [
            $title = TextInput::make('title')
                ->label(trans('laravel-filament-page-manager::messages.title'))
                ->required()
                ->columnSpanFull(),

            Slug::make('slug')
                ->label(trans('laravel-filament-page-manager::messages.slug'))
                ->fromField($title, function (Get $get) {
                    $value = $get('special');

                    return ! empty($value) && PageManager::special($value)?->pageSlug() !== null;
                })
                ->readOnly(function (Get $get) {
                    $value = $get('special');
                    if (! empty($value)) {
                        return PageManager::special($value)?->pageSlug() !== null;
                    }

                    return false;
                })
                ->required()
                ->string()
                ->regex('/^(\/|[a-zA-Z0-9-_]+)$/')
                ->unique(
                    config('laravel-filament-page-manager.model', Page::class),
                    'slug',
                    ignoreRecord: true,
                    modifyRuleUsing: function (Unique $rule, Get $get) {
                        return $rule->where('locale', $get('locale'));
                    }
                ),

            Select::make('special')
                ->afterStateHydrated(function (Select $component, $state) {
                    if ($state instanceof Special) {
                        $component->state($state->key());
                    }
                })
                ->label(trans('laravel-filament-page-manager::messages.special'))
                ->options(fn () => PageManager::specialPages()
                    ->mapWithKeys(fn (Special $special) => [
                        $special->key() => '<span class="flex gap-2 items-center">'.($special->icon() ? svg($special->icon(), 'h-4 w-4')->toHtml() : '').'<span>'.$special->name().'</span></span>',
                    ])
                    ->toArray())
                ->native(false)
                ->allowHtml()
                ->afterStateUpdated(function ($state, Set $set) {
                    if (! empty($state)) {
                        $special = PageManager::special($state);
                        $slug = $special?->pageSlug();
                        if ($slug !== null) {
                            $set('slug', $slug);
                        }
                        $template = $special?->template();
                        if ($template !== null) {
                            $set('template', $template->key());
                        }
                    }
                })
                ->reactive(),

            Locale::make('locale')
                ->required()
                ->reactive(),

            Select::make('template')
                ->afterStateHydrated(function (Select $component, $state) {
                    if ($state instanceof PageTemplate) {
                        $component->state($state->key());
                    }
                })
                ->label(trans('laravel-filament-page-manager::messages.template'))
                ->options(function (Get $get) {
                    $value = $get('special');
                    if (! empty($value)) {
                        $template = PageManager::special($value)?->template();
                        if ($template !== null) {
                            return [$template->key() => $template->name()];
                        }
                    }

                    return PageManager::templates()->mapWithKeys(fn (PageTemplate $template) => [$template->key() => $template->name()]);
                })
                ->required()
                ->live()
                ->afterStateUpdated(function (Select $component) {
                    $component
                        ->getContainer()->getParentComponent()?->getContainer()
                        ->getComponent('template_tab')
                        ?->fill();
                }),

            SelectGuard::make('guard')
                ->setGuards(config('laravel-filament-page-manager.guards', [])),

            Select::make('parent')
                ->label(trans('laravel-filament-page-manager::messages.parent'))
                ->searchable(['title', 'slug'])
                ->relationship(
                    titleAttribute: 'title',
                    modifyQueryUsing: fn (Builder|Page $query, Get $get) => $query->withLocale($get('locale')),
                    ignoreRecord: true
                )
                ->helperText(trans('laravel-filament-page-manager::messages.parent_helper_text')),

            Section::make(trans('laravel-filament-page-manager::messages.panel_publication'))
                ->columns()
                ->schema([
                    PublicationStatus::make('publication_status'),
                    PublishedAt::make('published_at'),
                    ExpiredAt::make('expired_at'),
                    PublishedFirstAt::make('published_first_at'),
                ]),

            Hidden::make('locale_parent_id'),
        ];
    }

    protected static function tabSeo(): array
    {
        return [
            TextInput::make('meta->seo_canonical_url')
                ->label(trans('laravel-filament-page-manager::messages.seo_canonical_url'))
                ->url()
                ->string()
                ->maxLength(191),
            ...static::getFormSEOFields(),
        ];
    }

    protected static function templateTab(): Tabs\Tab
    {
        $getTemplate = static function (Get $get) {
            $template = $get('template');
            if ($template !== null) {
                $template = PageManager::template($template);
                if ($template !== null) {
                    return $template;
                }
            }

            return null;
        };

        return Tabs\Tab::make('template_tab')
            ->label(function (Get $get) use ($getTemplate) {
                $template = $getTemplate($get);
                if ($template !== null) {
                    return $template->name();
                }

                return trans('laravel-filament-page-manager::messages.template');
            })
            ->schema(function (Get $get, Set $set) use ($getTemplate) {
                $template = $getTemplate($get);
                if ($template !== null) {
                    if (count($template->fields()) === 1 && $template->fields()[0] instanceof Tabs\Tab) {
                        $field = collect($template->fields())->first();
                        if ($field instanceof Tabs\Tab) {
                            return $field->getChildComponents();
                        }
                    }
                    foreach ($template->casts() as $key => $cast) {
                        $set('extras.'.$key, $get('extras.'.$key));
                    }

                    return $template->fields();
                }

                return [];
            })
            ->hidden(function (Get $get) use ($getTemplate) {
                $template = $getTemplate($get);

                return $template === null;
            })
            ->statePath('extras')
            ->key('template_tab');
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label(trans('laravel-filament-page-manager::messages.id'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('title')
                    ->label(trans('laravel-filament-page-manager::messages.title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(trans('laravel-filament-page-manager::messages.slug'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                LocaleColumn::make('locale')
                    ->sortable(),
                TranslationsColumn::make('translations'),

                TextColumn::make('template')
                    ->formatStateUsing(fn (PageTemplate $state) => $state->name())
                    ->label(trans('laravel-filament-page-manager::messages.template'))
                    ->sortable()
                    ->badge()
                    ->toggleable(),

                TextColumn::make('special')
                    ->formatStateUsing(fn (?Special $state) => $state?->name())
                    ->label(trans('laravel-filament-page-manager::messages.special'))
                    ->icon(fn (?Special $state) => $state?->icon())
                    ->sortable()
                    ->badge()
                    ->toggleable(),

                GuardColumn::make('guard')
                    ->setGuards(config('laravel-filament-page-manager.guards', [])),

                PublicationColumn::make('publication_status')
                    ->sortable()
                    ->toggleable(),

                static::getTableSEOBadgeColumn(),

                TextColumn::make('created_at')
                    ->label(trans('laravel-filament-page-manager::messages.created_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label(trans('laravel-filament-page-manager::messages.updated_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                LocaleFilter::make('locale'),
                PublicationStatusFilter::make('publication_status'),
                SelectFilter::make('template')
                    ->label(trans('laravel-filament-page-manager::messages.template'))
                    ->options(fn () => PageManager::templates()->mapWithKeys(fn (PageTemplate $template) => [$template->key() => $template->name()])),
                SelectFilter::make('special')
                    ->label(trans('laravel-filament-page-manager::messages.special'))
                    ->options(fn () => PageManager::specialPages()->mapWithKeys(fn (Special $template) => [$template->key() => $template->name()])),
                GuardFilter::make('guard')
                    ->setGuards(config('laravel-filament-page-manager.guards', [])),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                ActionGroup::make([
                    PreviewAction::make(),
                    ViewAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    PublicationBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'view' => Pages\ViewPage::route('/{record:id}'),
            'edit' => Pages\EditPage::route('/{record:id}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug'];
    }
}
