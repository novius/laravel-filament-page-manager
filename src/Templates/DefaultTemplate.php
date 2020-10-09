<?php

namespace Novius\LaravelNovaPageManager\Templates;

use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Trix;

class DefaultTemplate extends AbstractPageTemplate
{
    public function templateName(): string
    {
        return trans('laravel-nova-page-manager::template.default_template');
    }

    public function templateUniqueKey(): string
    {
        return 'default';
    }

    public function fields(): array
    {
        return [
            Text::make(trans('laravel-nova-page-manager::template.field_title'), 'title')
                ->rules('required'),

            Text::make(trans('laravel-nova-page-manager::template.field_subtitle'), 'subtitle')
                ->rules('required'),

            Trix::make(trans('laravel-nova-page-manager::template.field_content'), 'subtitle')
                ->rules('required'),
        ];
    }
}
