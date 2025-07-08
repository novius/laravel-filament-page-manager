<?php

namespace Novius\LaravelFilamentPageManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Illuminate\Support\Facades\Route;
use Novius\LaravelFilamentPageManager\Facades\PageManager;
use Novius\LaravelFilamentPageManager\Models\Page;

class HandlePages
{
    public function handle(Request $request, Closure $next, ?string $specialKey = null)
    {
        if ($specialKey !== null) {
            $special = PageManager::special($specialKey);
            $page = PageManager::model()::getSpecialPage($special, app()->getLocale());
            $request->route()?->setParameter('page', $page);
        }

        $page = $request->route()?->parameter('page');
        if ($page instanceof Page && $page->guard !== null) {
            $middleware = Route::resolveMiddleware(['auth:'.$page->guard]);

            return resolve(Pipeline::class)
                ->send($request)
                ->through($middleware)
                ->then(function ($request) use ($next) {
                    return $next($request);
                });
        }

        return $next($request);
    }
}
