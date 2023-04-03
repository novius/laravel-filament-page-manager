<?php

namespace Novius\LaravelNovaPageManager\Helpers;

use Illuminate\Support\Collection;
use Laravel\Nova\Resource;
use Novius\LaravelNovaPageManager\Templates\AbstractPageTemplate;

class TemplatesHelper
{
    public static function getTemplates(Resource $resource = null): Collection
    {
        return collect(config('laravel-nova-page-manager.templates', []))
            ->map(function ($templateClass) use ($resource) {
                if (! class_exists($templateClass)) {
                    return null;
                }

                $template = new $templateClass($resource);
                if (! $template instanceof AbstractPageTemplate) {
                    return null;
                }

                return [
                    'templateKey' => $template->templateUniqueKey(),
                    'template' => $template,
                ];
            })->filter();
    }

    public static function getTemplate(string $templateKey, Resource $resource): ?AbstractPageTemplate
    {
        $template = static::getTemplates($resource)->firstWhere('templateKey', $templateKey);
        if (empty($template)) {
            return null;
        }

        return $template['template'];
    }
}
