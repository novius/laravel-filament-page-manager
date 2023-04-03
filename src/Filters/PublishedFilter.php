<?php

namespace Novius\LaravelNovaPageManager\Filters;

use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class PublishedFilter extends Filter
{
    public const PUBLISHED = 1;

    public const NOT_PUBLISHED = 2;

    /**
     * The filter's component.
     *
     * @var string
     */
    public $component = 'select-filter';

    /**
     * Apply the filter to the given query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $value
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Request $request, $query, $value)
    {
        if ((int) $value === self::PUBLISHED) {
            return $query->published();
        }

        if ((int) $value === self::NOT_PUBLISHED) {
            return $query->notPublished();
        }

        return $query;
    }

    /**
     * Get the filter's available options.
     */
    public function options(Request $request): array
    {
        return [
            'Publié' => self::PUBLISHED,
            'Non publié' => self::NOT_PUBLISHED,
        ];
    }
}
