<?php

namespace Novius\LaravelNovaPageManager\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
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
 * @property string title
 * @property string slug
 * @property string locale
 * @property string template
 * @property int parent_id
 * @property int locale_parent_id
 * @property PublicationStatus $publication_status
 * @property Carbon|null $published_first_at
 * @property Carbon|null $published_at
 * @property Carbon|null $expired_at
 * @property string preview_token
 * @property array extras
 * @property Carbon created_at
 * @property Carbon updated_at
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
 * @method static Builder|Page newModelQuery()
 * @method static Builder|Page newQuery()
 * @method static Builder|Page notPublished()
 * @method static Builder|Page onlyDrafted()
 * @method static Builder|Page onlyExpired()
 * @method static Builder|Page onlyWillBePublished()
 * @method static Builder|Page published()
 * @method static Builder|Page query()
 * @method static Builder|Page withLocale(?string $locale)
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

    protected $table = 'page_manager_pages';

    protected $guarded = ['id'];

    protected $casts = [
        'extras' => 'json',
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

            $locales = config('laravel-nova-page-manager.locales', []);
            if (empty($page->locale) && count($locales) === 1) {
                $page->locale = array_key_first($locales);
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
                ->setOgImageDisk(config('laravel-nova-page-manager.og_image_disk', 'public'))
                ->setOgImagePath(config('laravel-nova-page-manager.og_image_path', '/'));
        }

        return $this->metaConfig;
    }

    protected LinkableConfig $_linkableConfig;

    public function linkableConfig(): LinkableConfig
    {
        if (! isset($this->_linkableConfig)) {
            $this->_linkableConfig = new LinkableConfig(
                routeName: config('laravel-nova-page-manager.front_route_name'),
                routeParameterName: config('laravel-nova-page-manager.front_route_parameter'),
                optionLabel: 'title',
                optionGroup: trans('laravel-nova-page-manager::page.linkableGroup'),
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

    protected function seoCanonicalUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                return Arr::get($this->{$this->getMetaColumn()}, 'seo_canonical_url', $this->url());
            }
        );
    }
}
