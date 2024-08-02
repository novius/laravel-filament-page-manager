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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Novius\LaravelMeta\Enums\IndexFollow;
use Novius\LaravelMeta\MetaModelConfig;
use Novius\LaravelMeta\Traits\HasMeta;
use Novius\LaravelPublishable\Enums\PublicationStatus;
use Novius\LaravelPublishable\Traits\Publishable;
use Novius\LaravelTranslatable\Traits\Translatable;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
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

    public function url(): ?string
    {
        $routeName = config('laravel-nova-page-manager.front_route_name');
        $parameter = $this->getUrlParameter();

        if ($routeName === null || ! $this->exists || ! $parameter) {
            return null;
        }

        return route($routeName, [
            $parameter => $this->slug,
        ]);
    }

    public function previewUrl(): ?string
    {
        $routeName = config('laravel-nova-page-manager.front_route_name');
        $parameter = $this->getUrlParameter();

        if ($routeName === null || ! $this->exists || ! $parameter) {
            return null;
        }

        $params = [
            $parameter => $this->slug,
        ];

        $guard = config('laravel-nova-page-manager.guard_preview');
        if (empty($guard) && ! $this->isPublished()) {
            $params['previewToken'] = $this->preview_token;
        }

        return route($routeName, $params);
    }

    protected function getUrlParameter(): ?string
    {
        $parameter = config('laravel-nova-page-manager.front_route_parameter');

        if (! empty($parameter)) {
            return $parameter;
        }

        $routeName = config('laravel-nova-page-manager.front_route_name');
        if (empty($routeName)) {
            return null;
        }

        $route = Route::getRoutes()->getByName($routeName);
        if (! $route) {
            return null;
        }

        if (! preg_match('/({\w+})/', $route->uri(), $matches)) {
            return null;
        }

        return substr($matches[0], 1, -1);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $guard = config('laravel-nova-page-manager.guard_preview');
        $query = static::where('locale', app()->currentLocale());

        if (! empty($guard) && Auth::guard($guard)->check()) {
            return $this->resolveRouteBindingQuery($query, $value, $field)->first();
        }

        if (request()->has('previewToken')) {
            $query->where(/**
             * @throws ContainerExceptionInterface
             * @throws NotFoundExceptionInterface
             */ function (Builder $query) {
                $query->published()
                    ->orWhere('preview_token', request()->get('previewToken'));
            });

            return $this->resolveRouteBindingQuery($query, $value, $field)->first();
        }

        return $this->resolveRouteBindingQuery($query->published(), $value, $field)->first();
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

    protected function seoCanonicalUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                return Arr::get($this->{$this->getMetaColumn()}, 'seo_canonical_url', $this->url());
            }
        );
    }
}
