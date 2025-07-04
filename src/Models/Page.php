<?php

namespace Novius\LaravelFilamentPageManager\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Novius\LaravelFilamentPageManager\Casts\AsSpecialPage;
use Novius\LaravelFilamentPageManager\Casts\AsTemplate;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;
use Novius\LaravelFilamentPageManager\Contracts\Special;
use Novius\LaravelFilamentPageManager\Facades\PageManager;
use Novius\LaravelFilamentPageManager\SpecialPages\Homepage;
use Novius\LaravelJsonCasted\Casts\JsonWithCasts;
use Novius\LaravelLinkable\Configs\LinkableConfig;
use Novius\LaravelLinkable\Traits\Linkable;
use Novius\LaravelMeta\Enums\IndexFollow;
use Novius\LaravelMeta\MetaModelConfig;
use Novius\LaravelMeta\Traits\HasMeta;
use Novius\LaravelPublishable\Enums\PublicationStatus;
use Novius\LaravelPublishable\Traits\Publishable;
use Novius\LaravelTranslatable\Support\TranslatableModelConfig;
use Novius\LaravelTranslatable\Traits\Translatable;
use RuntimeException;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Class Page
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $locale
 * @property PageTemplate $template
 * @property int $parent_id
 * @property int $locale_parent_id
 * @property ?Special $special
 * @property PublicationStatus $publication_status
 * @property Carbon|null $published_first_at
 * @property Carbon|null $published_at
 * @property Carbon|null $expired_at
 * @property string $preview_token
 * @property array<array-key, mixed>|null $meta
 * @property Fluent $extras
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read string|null $seo_robots
 * @property-read string|null $seo_title
 * @property-read string|null $seo_description
 * @property-read string|null $seo_keywords
 * @property-read string|null $seo_canonical_url
 * @property-read string|null $og_type
 * @property-read string|null $og_title
 * @property-read string|null $og_description
 * @property-read string|null $og_image
 * @property-read string|null $og_image_url
 * @property-read Collection<int, Page> $translations
 * @property-read Collection<int, Page> $translationsWithDeleted
 * @property-read static|null $parent
 * @property-read Collection<int, static> $children
 *
 * @method static Builder<static>|Page homepage()
 * @method static Builder<static>|Page indexableByRobots()
 * @method static Builder<static>|Page newModelQuery()
 * @method static Builder<static>|Page newQuery()
 * @method static Builder<static>|Page notIndexableByRobots()
 * @method static Builder<static>|Page notPublished()
 * @method static Builder<static>|Page onlyDrafted()
 * @method static Builder<static>|Page onlyExpired()
 * @method static Builder<static>|Page onlyWillBePublished()
 * @method static Builder<static>|Page published()
 * @method static Builder<static>|Page query()
 * @method static Builder<static>|Page withLocale(?string $locale)
 *
 * @mixin Eloquent
 */
class Page extends Model
{
    use HasMeta;
    use HasSlug;
    use Linkable;
    use Publishable;
    use Translatable;

    protected $table = 'pages';

    protected $guarded = ['id'];

    protected $casts = [
        'template' => AsTemplate::class,
        'special' => AsSpecialPage::class,
        'extras' => JsonWithCasts::class.':getExtrasCasts',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(static function (Page $page) {
            if ($page->exists && $page->id === $page->parent_id) {
                throw new RuntimeException('Page : parent_id can\'t be same as primary key.');
            }

            if ($page->special?->pageSlug() !== null) {
                $page->slug = $page->special->pageSlug();
            }

            if (empty($page->preview_token)) {
                $page->preview_token = Str::random();
            }

            if (empty($page->locale) && PageManager::locales()->count() === 1) {
                $page->locale = PageManager::locales()->first()->code;
            }
        });
        static::saved(static function (Page $page) {
            if ($page->special && app()->routesAreCached()) {
                Artisan::call('route:clear');
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id', 'id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->extraScope(fn (Builder|Page $query) => $query->where('locale', $this->locale))
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->skipGenerateWhen(fn () => $this->special?->pageSlug() !== null)
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getMetaConfig(): MetaModelConfig
    {
        if (! isset($this->metaConfig)) {
            $this->metaConfig = MetaModelConfig::make()
                ->setDefaultSeoRobots(IndexFollow::index_follow)
                ->setFallbackTitle('title')
                ->setOgImageDisk(config('laravel-filament-page-manager.og_image_disk',
                    'public'))
                ->setOgImagePath(config('laravel-filament-page-manager.og_image_path',
                    '/'));
        }

        return $this->metaConfig;
    }

    protected LinkableConfig $_linkableConfig;

    public function linkableConfig(): LinkableConfig
    {
        if (! isset($this->_linkableConfig)) {
            $this->_linkableConfig = new LinkableConfig(
                routeName: 'page-manager.page',
                routeParameterName: 'page',
                optionLabel: 'title',
                optionGroup: trans('laravel-filament-page-manager::messages.linkableGroup'),
                resolveQuery: function (Builder|Page $query) {
                    $query->where('locale', app()->currentLocale());
                },
                resolveNotPreviewQuery: function (Builder|Page $query) {
                    $query->published();
                },
                previewTokenField: 'preview_token'
            );
        }

        return $this->_linkableConfig;
    }

    public function translatableConfig(): TranslatableModelConfig
    {
        return new TranslatableModelConfig(PageManager::locales()->pluck('code')->toArray());
    }

    public function getExtrasCasts(): array
    {
        /** @phpstan-ignore nullsafe.neverNull,nullCoalesce.expr */
        return $this->template?->casts() ?? [];
    }

    public function scopeHomepage(Builder|Page $query): void
    {
        $query->where('special', (new HomePage)->key());
    }

    public static function getHomePage(?string $locale = null, ?Request $request = null): static
    {
        return static::query()
            ->homepage()
            ->withLocale($locale ?? app()->currentLocale())
            ->published()
            ->firstOrFail();
    }

    public static function getSpecialPage(string|Special $special, ?string $locale = null): ?static
    {
        if ($special instanceof Special) {
            $special = $special->key();
        } elseif (in_array(Special::class, class_implements($special), true)) {
            $special = (new $special)->key();
        }

        return static::query()
            ->where('special', $special)
            ->withLocale($locale ?? app()->currentLocale())
            ->published()
            ->first();
    }

    protected function seoCanonicalUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                return Arr::get($this->{$this->getMetaColumn()}, 'seo_canonical_url',
                    $this->url());
            }
        );
    }
}
