# Laravel Nova Page Manager

This package allows you to manage pages with custom templates.

## Requirements

* PHP >= 8.1
* Laravel Nova >= 4.0

## Installation

```sh
composer require novius/laravel-nova-page-manager
```

> **NOTE**: These instructions are for Laravel Nova >= 4.0. If you are using prior version, please
> see the [previous version's docs](https://github.com/novius/laravel-nova-page-manager/tree/1.x).

**Validator translation**

Please add this line to `resource/lang/{locale}/validation.php` (on first level) :

```php
// EN version : resource/lang/en/validation.php
'unique_page' => 'The field :attribute must be unique in this language.',

// FR version : resource/lang/fr/validation.php
'unique_page' => 'Le champ :attribute doit être unique dans cette langue.',
``` 

**Front Stuff** 

If you want a pre-generated front controller and route, you can run following command :

```shell
php artisan page-manager:publish-front
``` 

This command appends a route to `routes/web.php` and creates a new `App\Http\Controllers\FrontPageController`.

## Configuration

Some options that you can override are available.

```sh
php artisan vendor:publish --provider="Novius\LaravelNovaPageManager\LaravelNovaPageManagerServiceProvider" --tag="config"
```

**Locales**

You can add / remove any locale from config files.

Example if you want 2 languages (FR and EN): 

```php
// ...

'locales' => [
    'en' => 'English',
    'fr' => 'French',
],

// ...
```

## Templates

To add a template, just add your custom class to `templates` array in configuration file.

Your class must extend `Novius\LaravelNovaPageManager\Templates\AbstractPageTemplate`.

Example : 

In `config/laravel-nova-page-manager.php`
```php
// ...

'templates' => [
    \App\Nova\Templates\StandardTemplate::class,
],
```

In `app/Nova/Templates/StandardTemplate.php`

```php
<?php

namespace App\Nova\Templates;

use Laravel\Nova\Fields\Trix;
use Novius\LaravelNovaPageManager\Templates\AbstractPageTemplate;

class StandardTemplate extends AbstractPageTemplate
{
    public function templateName(): string
    {
        return trans('laravel-nova-page-manager::template.standard_template');
    }

    public function templateUniqueKey(): string
    {
        return 'standard';
    }

    public function fields(): array
    {
        return [
            Trix::make(trans('laravel-nova-page-manager::template.field_content'), 'content'),
        ];
    }
}
``` 

## Lint

Run php-cs with:

```sh
composer run-script lint
```

## Contributing

Contributions are welcome!

Leave an issue on Github, or create a Pull Request.

## Licence

This package is under [GNU Affero General Public License v3](http://www.gnu.org/licenses/agpl-3.0.html) or (at your option) any later version.
