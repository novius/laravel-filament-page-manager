# Laravel Filament Page Manager

This package allows you to manage pages with custom templates.

## Requirements

* PHP >= 8.2
* Laravel >= 11.0
* Laravel Filament >= 3.3

## Installation

```sh
composer require novius/laravel-filament-page-manager
```

**Front Stuff** 

If you want a pre-generated front controller and route, you can run following command :

```shell
php artisan page-manager:publish-front
``` 

This command appends a route to `routes/web.php` and creates a new `App\Http\Controllers\PageController`.

In Page templates use the documentation of [laravel-meta](https://github.com/novius/laravel-meta?tab=readme-ov-file#front) to implement meta tags

## Configuration

Some options that you can override are available.

```sh
php artisan vendor:publish --provider="Novius\LaravelFilamentPageManager\LaravelFilamentPageManagerServiceProvider" --tag="config"
```

## Templates

To add a template, just add your custom class to `templates` array in configuration file or place it under `App\Templates\Pages` namespace

Your class must implement `Novius\LaravelFilamentPageManager\Contracts\PageTemplate`.

Example : 

In `app/Templates/Pages/StandardTemplate.php`

```php
<?php

namespace App\Templates;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;

class StandardTemplate extends PageTemplate
{
    public function templateName(): string
    {
        return trans('laravel-filament-page-manager::template.standard_template');
    }

    public function templateUniqueKey(): string
    {
        return 'standard';
    }

    public function fields(): array
    {
        return [
            RichEditor::make('content')
                ->label('Content'),
            DatePicker::make('date')
                ->label('Date'),
        ];
    }
    
    public function casts() : array
    {
        return [
            'date' => 'date',        
        ];
    }
}
``` 

To use the specific template fields :

```php
$page = \Novius\LaravelFilamentPageManager\Models\Page::where('template', 'standard')->first();

$content = $page->extras['content'];

// Date will be a Carbon instance, thanks to the cast
$date = $page->extras['date'];
```

## Lint

Run php-cs with:

```sh
composer run-script lint
```

## Contributing

Contributions are welcome!

Leave an issue on GitHub, or create a Pull Request.

## Licence

This package is under [GNU Affero General Public License v3](http://www.gnu.org/licenses/agpl-3.0.html) or (at your option) any later version.
