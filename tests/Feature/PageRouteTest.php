<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Novius\LaravelFilamentPageManager\Facades\PageManager;

function matchedRouteName(string $url): ?string
{
    return Route::getRoutes()->match(Request::create($url))->getName();
}

it('registers the page route as a fallback route', function () {
    PageManager::routes();

    expect(Route::getRoutes()->match(Request::create('/a-propos')))
        ->getName()->toBe('page-manager.page')
        ->isFallback->toBeTrue();
});

it('lets the routes without domain win over the page route bound to a domain', function () {
    Route::domain('fr.test')->group(fn () => PageManager::routes());
    Route::get('livewire/livewire.js', fn () => 'livewire script');

    $this->get('http://fr.test/livewire/livewire.js')
        ->assertOk()
        ->assertSee('livewire script');

    expect(matchedRouteName('http://fr.test/a-propos'))->toBe('page-manager.page');
});

it('lets the routes declared after the page routes win', function () {
    PageManager::routes();
    Route::get('contact', fn () => 'contact form');

    $this->get('/contact')
        ->assertOk()
        ->assertSee('contact form');

    expect(matchedRouteName('/a-propos'))->toBe('page-manager.page');
});

it('keeps the homepage route before the page route', function () {
    PageManager::routes();

    expect(matchedRouteName('/'))->toBe('page-manager.homepage');
});

it('keeps serving pages when the project fallback is declared after the page routes', function () {
    PageManager::routes();
    Route::fallback(fn () => 'project fallback')->name('project.fallback');

    expect(matchedRouteName('/a-propos'))->toBe('page-manager.page')
        ->and(matchedRouteName('/admin/unknown'))->toBe('project.fallback');
});

it('responds 404 when the page parameter is resolved to null', function () {
    Route::bind('page', fn () => null);
    Route::middleware(SubstituteBindings::class)->group(fn () => PageManager::routes());

    $this->get('/unknown-page')->assertNotFound();
});

it('hands the pages to a project fallback declared before the page routes', function () {
    Route::fallback(fn () => 'project fallback')->name('project.fallback');
    PageManager::routes();

    expect(matchedRouteName('/a-propos'))->toBe('project.fallback');
});
