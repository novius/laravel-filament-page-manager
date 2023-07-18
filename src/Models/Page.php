<?php

namespace Novius\LaravelNovaPageManager\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Novius\LaravelPublishable\Enums\PublicationStatus;
use Novius\LaravelPublishable\Traits\Publishable;
use Novius\LaravelTranslatable\Traits\Translatable;
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
 * @property string seo_title
 * @property string seo_description
 * @property int seo_robots
 * @property string seo_canonical_url
 * @property string og_title
 * @property string og_description
 * @property string og_image
 * @property array extras
 * @property Carbon created_at
 * @property Carbon updated_at
 */
class Page extends Model
{
    use HasSlug;
    use Publishable;
    use Translatable;

    public const ROBOTS_INDEX_FOLLOW = 1;

    public const ROBOTS_INDEX_NOFOLLOW = 2;

    public const ROBOTS_NOINDEX_NOFOLLOW = 3;

    public const ROBOTS_NOINDEX_FOLLOW = 4;

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
        static::saving(function ($page) {
            if ($page->exists && $page->id === $page->parent_id) {
                throw new \Exception('Page : parent_id can\'t be same as primary key.');
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

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children()
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

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $guard = config('laravel-nova-page-manager.guard_preview');
        if (! empty($guard) && Auth::guard($guard)->check()) {
            return parent::resolveRouteBinding($value, $field);
        }

        if (request()->has('previewToken')) {
            $query = static::where(function (Builder $query) {
                $query->published()
                    ->orWhere('preview_token', request()->get('previewToken'));
            });

            return $this->resolveRouteBindingQuery($query, $value, $field)->first();
        }

        return $this->resolveRouteBindingQuery(static::published(), $value, $field)->first();
    }

    public function canBeIndexedByRobots(): bool
    {
        return in_array($this->seo_robots, static::robotsCanIndexStatus());
    }

    public function robotsDirective(): ?string
    {
        if (empty($this->seo_robots)) {
            return null;
        }

        return static::findRobotDirective($this->seo_robots)['value_for_robots'] ?? null;
    }

    public static function robotsDirectives(): array
    {
        return [
            self::ROBOTS_INDEX_FOLLOW => 'index, follow',
            self::ROBOTS_INDEX_NOFOLLOW => 'index, nofollow',
            self::ROBOTS_NOINDEX_NOFOLLOW => 'noindex, nofollow',
            self::ROBOTS_NOINDEX_FOLLOW => 'noindex, follow',
        ];
    }

    /**
     * Get all robots status "indexable"
     *
     * @return array|int[]
     */
    public static function robotsCanIndexStatus(): array
    {
        return [
            self::ROBOTS_INDEX_FOLLOW,
            self::ROBOTS_INDEX_NOFOLLOW,
        ];
    }

    public static function findRobotDirective(int $type): ?array
    {
        $directive = static::robotsDirectives()[$type] ?? null;
        if (empty($directive)) {
            return null;
        }

        return [
            'key' => $type,
            'value_for_robots' => $directive,
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }
}
