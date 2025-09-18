<?php

use Novius\LaravelFilamentPageManager\Filament\Resources\Pages\PageResource;
use Novius\LaravelFilamentPageManager\Models\Page;
use Novius\LaravelFilamentPageManager\SpecialPages\Homepage;
use Novius\LaravelFilamentPageManager\SpecialPages\Page404;
use Novius\LaravelFilamentPageManager\Templates\DefaultTemplate;

return [
    'model' => Page::class,

    'filamentResource' => PageResource::class,

    // If you want to restrict the list of possible locals. By default, uses all the locals installed
    'locales' => [
        // 'en',
    ],

    'og_image_disk' => 'public',

    'og_image_path' => 'pages/og',

    // If you want to exclude some pattern for the route page parameter
    'route_parameter_where' => '^((?!admin).)+$',

    'autoload_templates_in' => app_path('Pages/Templates'),

    'templates' => [
        DefaultTemplate::class,
    ],

    'autoload_special_in' => app_path('Pages/Special'),

    'special' => [
        Homepage::class,
        Page404::class,
    ],

    // If you want certain pages to be protected by a Guard, indicate the list of guards you want to make available (must be in `config('auth.guards')` keys)
    'guards' => [
    ],
];
