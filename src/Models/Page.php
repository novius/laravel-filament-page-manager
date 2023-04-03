<?php

namespace Novius\LaravelNovaPageManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Novius\LaravelNovaContexts\Traits\HasContext;
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
 * @property Carbon publication_date
 * @property Carbon end_publication_date
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
    use HasContext;
    use HasSlug;

    public const ROBOTS_INDEX_FOLLOW = 1;

    public const ROBOTS_INDEX_NOFOLLOW = 2;

    public const ROBOTS_NOINDEX_NOFOLLOW = 3;

    public const ROBOTS_NOINDEX_FOLLOW = 4;

    protected $table = 'page_manager_pages';

    protected $guarded = ['id'];

    protected $casts = [
        'extras' => 'json',
        'publication_date' => 'datetime',
        'end_publication_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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

    public function localParent()
    {
        return $this->hasOne(static::class, 'id', 'locale_parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(static::class, 'parent_id', 'id');
    }

    public function isPublished(): bool
    {
        if (empty($this->publication_date)) {
            return false;
        }

        if ($this->publication_date->isAfter(Carbon::now())) {
            return false;
        }

        if (! empty($this->end_publication_date) && $this->end_publication_date->isBefore(Carbon::now())) {
            return false;
        }

        return true;
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('publication_date')
            ->where('publication_date', '<=', DB::raw('NOW()'))
            ->where(function ($query) {
                $query->whereNull('end_publication_date')
                    ->orWhere('end_publication_date', '>=', DB::raw('NOW()'));
            });
    }

    public function scopeNotPublished($query)
    {
        return $query->whereNull('publication_date')
            ->orWhere(function ($query) {
                $query->where('publication_date', '>', DB::raw('NOW()'))
                    ->orWhere(function ($query) {
                        $query->whereNotNull('end_publication_date')
                            ->where('end_publication_date', '<', DB::raw('NOW()'));
                    });
            });
    }

    public function url(): ?string
    {
        $routeName = config('laravel-nova-page-manager.front_route_name');
        if (empty($routeName) || ! Route::has($routeName) || ! $this->exists) {
            return null;
        }

        return route($routeName, [
            'slug' => $this->slug,
        ]);
    }

    public function previewUrl(): ?string
    {
        $routeName = config('laravel-nova-page-manager.front_route_name');
        if (empty($routeName) || ! Route::has($routeName) || ! $this->exists) {
            return null;
        }

        $params = [
            'slug' => $this->slug,
        ];

        if (! $this->isPublished()) {
            $params['previewToken'] = $this->preview_token;
        }

        return route($routeName, $params);
    }

    public function contextFieldName(): string
    {
        return 'locale';
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
