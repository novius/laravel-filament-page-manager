<?php

return [

    'resources' => [
        \Novius\LaravelNovaPageManager\Resources\Page::class,
    ],

    'locales' => [
        'en' => 'English',
    ],

    'og_image_disk' => 'public',

    'og_image_path' => 'pages/og',

    'front_route_name' => 'page-manager.page',

    'templates' => [
        \Novius\LaravelNovaPageManager\Templates\DefaultTemplate::class,
    ],
];
