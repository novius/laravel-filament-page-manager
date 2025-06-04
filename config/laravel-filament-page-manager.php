<?php

use Novius\LaravelFilamentPageManager\Templates\DefaultTemplate;

return [
    'pageResource' => \Novius\LaravelFilamentPageManager\Filament\Resources\PageResource::class,

    // If you want to restrict the list of possible locals. By default, uses all the locals installed
    'locales' => [
        // 'en',
    ],

    'og_image_disk' => 'public',

    'og_image_path' => 'pages/og',

    'front_route_name' => 'page-manager.page',

    'front_route_parameter' => 'page',

    'guard_preview' => null,

    'autoload_templates_in' => app_path('Templates/Pages'),

    'templates' => [
        //        DefaultTemplate::class,
    ],
];
