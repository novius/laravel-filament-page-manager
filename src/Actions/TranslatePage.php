<?php

namespace Novius\LaravelNovaPageManager\Actions;

use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Novius\LaravelNovaPageManager\Models\Page;

class TranslatePage extends Action
{
    /**
     * Perform the action on the given models.
     *
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        if ($models->count() > 1) {
            return Action::danger(trans('laravel-nova-page-manager::errors.action_only_available_for_single_menu'));
        }

        $pageToTranslate = $models->first();
        $locale = $fields->locale;
        if ($pageToTranslate->locale === $locale) {
            return Action::danger(trans('laravel-nova-page-manager::errors.menu_already_translated'));
        }

        if (! empty($pageToTranslate->locale_parent_id)) {
            $pageToTranslate = $pageToTranslate->localParent;
            if (empty($pageToTranslate)) {
                return Action::danger(trans('laravel-nova-page-manager::errors.error_during_menu_translation'));
            }
        }

        $otherPageAlreadyExists = Page::query()
            ->where('locale', $locale)
            ->where('locale_parent_id', $pageToTranslate->id)
            ->exists();

        if ($otherPageAlreadyExists) {
            return Action::danger(trans('laravel-nova-page-manager::errors.menu_already_translated'));
        }

        $translatedItem = $pageToTranslate->replicate();
        $translatedItem->title = $fields->title;
        $translatedItem->slug = null;
        $translatedItem->locale = $locale;
        $translatedItem->locale_parent_id = $pageToTranslate->id;

        if (! $translatedItem->save()) {
            return Action::danger(trans('laravel-nova-page-manager::errors.error_during_menu_translation'));
        }

        return Action::message(trans('laravel-nova-page-manager::menu.successfully_translated_menu'));
    }

    /**
     * Get the fields available on the action.
     */
    public function fields(NovaRequest $request): array
    {
        $locales = config('laravel-nova-page-manager.locales', []);

        return [
            Text::make(trans('laravel-nova-page-manager::page.title'), 'title')
                ->required()
                ->rules('required', 'max:255'),

            Select::make(trans('laravel-nova-page-manager::page.locale'), 'locale')
                ->options($locales)
                ->displayUsingLabels()
                ->rules('required', 'in:'.implode(',', array_keys($locales))),
        ];
    }
}
