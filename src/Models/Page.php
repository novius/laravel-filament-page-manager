<?php

namespace Novius\LaravelFilamentPageManager\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Novius\LaravelFilamentPageManager\Facades\PageManager;
use Novius\LaravelJsonCasted\Casts\JsonWithCasts;
use Novius\LaravelLinkable\Configs\LinkableConfig;
use Novius\LaravelLinkable\Traits\Linkable;
use Novius\LaravelMeta\Enums\IndexFollow;
use Novius\LaravelMeta\MetaModelConfig;
use Novius\LaravelMeta\Traits\HasMeta;
use Novius\LaravelPublishable\Enums\PublicationStatus;
use Novius\LaravelPublishable\Traits\Publishable;
use Novius\LaravelTranslatable\Traits\Translatable;
use RuntimeException;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Class Page
 *
 * @property string $title
 * @property string $slug
 * @property string $locale
 * @property string $template
 * @property int $parent_id
 * @property int $locale_parent_id
 * @property PublicationStatus $publication_status
 * @property Carbon|null $published_first_at
 * @property Carbon|null $published_at
 * @property Carbon|null $expired_at
 * @property string $preview_token
 * @property array $extras
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read bool $is_home
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
 *
 * @method static Builder<static>|Page homepage()
 * @method static Builder<static>|Page newModelQuery()
 * @method static Builder<static>|Page newQuery()
 * @method static Builder<static>|Page notPublished()
 * @method static Builder<static>|Page onlyDrafted()
 * @method static Builder<static>|Page onlyExpired()
 * @method static Builder<static>|Page onlyWillBePublished()
 * @method static Builder<static>|Page published()
 * @method static Builder<static>|Page query()
 * @method static Builder<static>|Page withLocale(?string $locale)
 *
 * @mixin Model
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
        'extras' => JsonWithCasts::class.':getExtrasCasts',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(static function ($page) {
            if ($page->exists && $page->id === $page->parent_id) {
                throw new RuntimeException('Page : parent_id can\'t be same as primary key.');
            }

            if (empty($page->preview_token)) {
                $page->preview_token = Str::random();
            }

            if (empty($page->locale) && PageManager::locales()->count() === 1) {
                $page->locale = PageManager::locales()->first()->code;
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
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getMetaConfig(): MetaModelConfig
    {
        if (! isset($this->metaConfig)) {
            $this->metaConfig = MetaModelConfig::make()
                ->setDefaultSeoRobots(IndexFollow::index_follow)
                ->setFallbackTitle('title')
                ->setOgImageDisk(config('laravel-filament-page-manager.og_image_disk', 'public'))
                ->setOgImagePath(config('laravel-filament-page-manager.og_image_path', '/'));
        }

        return $this->metaConfig;
    }

    protected LinkableConfig $_linkableConfig;

    public function linkableConfig(): LinkableConfig
    {
        if (! isset($this->_linkableConfig)) {
            $this->_linkableConfig = new LinkableConfig(
                routeName: config('laravel-filament-page-manager.front_route_name'),
                routeParameterName: config('laravel-filament-page-manager.front_route_parameter'),
                optionLabel: 'title',
                optionGroup: trans('laravel-filament-page-manager::page.linkableGroup'),
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

    public function getExtrasCasts(): array
    {
        $template = $this->template ? PageManager::template($this->template) : null;

        return $template?->casts() ?? [];
    }

    public function scopeHomepage(Builder|Page $query): void
    {
        $query->where('slug', '/');
    }

    protected function isHome(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->slug === '/';
            }
        );
    }

    protected function seoCanonicalUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                return Arr::get($this->{$this->getMetaColumn()}, 'seo_canonical_url', $this->url());
            }
        );
    }
}
