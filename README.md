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

### Route 

Add to your `routes/web.php` file:

```php
    \Novius\LaravelFilamentPageManager\Facades\PageManager::routes();
``` 
## Configuration

Some options that you can override are available.

```sh
php artisan vendor:publish --provider="Novius\LaravelFilamentPageManager\LaravelFilamentPageManagerServiceProvider" --tag="config"
```

If you're overload the `Page` model (and so the filament resource), remember to change them in the config.

## Templates

To add a template, add your custom class to `templates` array in the configuration file or place it under `App\Pages\Templates` namespace if you keep this value for `autoload_templates_in`.

Your class must implement `Novius\LaravelFilamentPageManager\Contracts\PageTemplate`.

Example : 

In `app/Pages/Templates/StandardTemplate.php`

```php
<?php
namespace App\Pages\Templates;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;

class StandardTemplate implements PageTemplate
{
    public function key(): string
    {
        return 'standard';
    }

    public function name(): string
    {
        return trans('laravel-filament-page-manager::template.standard_template');
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

    public function view(): string
    {
        return 'templates/standard';
    }

    public function viewParameters(Request $request, Page $page): array
    {
        $settings = \App\Model\SettingsModel::instance();    
    
        return [
            'page' => $page,
            'settings' => $settings,
        ];
    }
}
``` 

You could also overload the `DefaultTemplate` class 

```php
<?php
namespace App\Pages\Templates;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Novius\LaravelFilamentPageManager\Templates\DefaultTemplate;

class StandardTemplate extends DefaultTemplate
{
    public function fields(): array
    {
        return [
            ...parent::fields(),        

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

    public function view(): string
    {
        return 'templates/standard';
    }
}
```

### Fields 

To use the specific template fields :

```php
$page = \Novius\LaravelFilamentPageManager\Models\Page::where('template', 'standard')->first();

$content = $page->extras['content'];

// Date will be a Carbon instance, thanks to the cast
$date = $page->extras['date'];
```

### View

In the template view, use the documentation of [laravel-meta](https://github.com/novius/laravel-meta?tab=readme-ov-file#front) to implement meta tags.

The page manager automatically calls the `CurrentModel::setModel()` method for you, with the current page displayed. 

## Special Pages

The page manager came with three special pages:
* Home page
* Page for 404 error

You could remove those special pages if you don't need them.

For the 404 pages to work, you must add middleware `HandleSpecialPages` to the Middleware group `Web`. In your `provider/app.php` file :

```php
use Illuminate\Routing\Middleware\SubstituteBindings;
use Novius\LaravelFilamentPageManager\Http\Middleware\HandleSpecialPages;

return Application::configure(basePath: dirname(__DIR__))
    //...
    ->withMiddleware(function (Middleware $middleware) {
        //...
        $middleware->web(remove: [
            SubstituteBindings::class,
        ]);
        $middleware->web(append: [
            HandleSpecialPages::class,
            SubstituteBindings::class,
        ]);
        //...
    })
    //...
    })->create();
```

### Custom Special page

You can create your owned special page. Add your custom class to `special` array in the configuration file or place it under `App\Pages\Special` namespace if you keep this value for `autoload_special_in`.

Here are two examples.

One for a contact page:

```php
<?php
namespace App\Pages\Special;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;
use Novius\LaravelFilamentPageManager\Contracts\Special;
use Novius\LaravelFilamentPageManager\Facades\PageManager;

class Contact implements Special
{
    public function key(): string
    {
        return 'contact';
    }

    public function name(): string
    {
        return 'Contact';
    }

    public function icon(): ?string
    {
        // you can return null if you d'ont want icon in the filament interface
        return 'heroicon-o-paper-airplane';
    }

    public function pageSlug(): ?string
    {
        // return null, let the user define is owned slug for this page
        return null;
    }

    public function template(): ?PageTemplate
    {
        // return null, let the user define is owned template for this page
        return null;
    }

    public function statusCode(): ?int
    {
        // return null, your page has a 200 status code
        return null;
    }

    public function routes(): void
    {
        // do nothing, your page will be handled by the main page manager route 
    }
}
```

Another for a page displaying a product :

```php
<?php
namespace App\Pages\Special;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Novius\LaravelFilamentPageManager\Contracts\Special;
use Novius\LaravelFilamentPageManager\Facades\PageManager;

class Product implements Special
{
    public function key(): string
    {
        return 'product';
    }

    public function name(): string
    {
        return 'Product';
    }

    public function icon(): ?string
    {
        return null;
    }

    public function pageSlug(): ?string
    {
        // return null, let the user define is owned slug for this page
        return null;
    }

    public function template(): ?PageTemplate
    {
        return \App\Pages\Templates\ProductTemplate::class;
    }

    public function statusCode(): ?int
    {
        // return null, your page has a 200 status code
        return null;
    }

    public function routes(): void
    {
        Route::get('{page}/{product}', function (Request $request, Page $page, Product $product) {
            return PageManager::render($request, $page);
        })
            ->where(['page' => config('laravel-filament-page-manager.route_parameter_where', '^((?!admin).)+$')])
            ->name('page-manager.product');
    }
}
```

Next in the method `viewParameters` of your template, you could do that:

```php
use Novius\LaravelFilamentPageManager\Contracts\PageTemplate;

class ProductTemplate implements PageTemplate
{
    // ...
    public function viewParameters(Request $request, Page $page): array
    {
        $product = Route::getCurrentRoute()?->parameter('product');
        CurrentModel::setModel($product);

        return [
            'page' => $page,
            'product' => $product,
        ];
    }
}

```

## Facade and Helpers

```php
use Novius\LaravelFilamentPageManager\Facades\PageManager;
use Novius\LaravelFilamentPageManager\Models\Page;
use Novius\LaravelFilamentPageManager\SpecialPages\Page404;

PageManager::templates();  // return Collection<string, PageTemplate>
PageManager::template('default'); // return the PageTemplate with the 'default' key
PageManager::specialPages();  // return Collection<string, Special>
PageManager::special('homepage'); // return the Special with the 'homepage' key
PageManager::routes(); // Use it in your `routes/web.php` file

Page::getHomePage('en'); // return the Page defined as special Homepage in locale 'en'
Page::getHomePage(); // return the Page defined as special Homepage in the current locale
Page::getSpecialPage('404', 'en'); // return the Page defined as special Page404 in locale 'en'
Page::getSpecialPage('404'); // return the Page defined as special Page404 in the current locale
Page::getSpecialPage(Page404::class); // return the Page defined as special Page404 in the current locale
Page::getSpecialPage(new Page404()); // return the Page defined as special Page404 in the current locale
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
