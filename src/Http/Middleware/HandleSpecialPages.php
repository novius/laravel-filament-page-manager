<?php

namespace Novius\LaravelFilamentPageManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Novius\LaravelFilamentPageManager\Facades\PageManager;
use Novius\LaravelFilamentPageManager\Models\Page;

class HandleSpecialPages
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->expectsJson()) {
            return $response;
        }

        $locale = app()->getLocale();
        foreach (PageManager::specialPages() as $special) {
            if ($response->getStatusCode() === $special->statusCode()) {
                /** @var class-string<Page> $pageClass */
                $pageClass = config('laravel-filament-page-manager.model', Page::class);
                $page = $pageClass::query()
                    ->where('special', $special->key())
                    ->where('locale', $locale)
                    ->published()
                    ->first();
                if ($page !== null) {
                    return response(PageManager::render($request, $page), $special->statusCode());
                }
            }
        }

        return $response;
    }
}
