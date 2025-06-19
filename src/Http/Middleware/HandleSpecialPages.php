<?php

namespace Novius\LaravelFilamentPageManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Novius\LaravelFilamentPageManager\Facades\PageManager;

class HandleSpecialPages
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        foreach (PageManager::specialPages() as $special) {
            if ($response->getStatusCode() === $special->statusCode()) {

            }
        }

        return $response;
    }
}
