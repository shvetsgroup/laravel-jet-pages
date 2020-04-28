<?php

namespace ShvetsGroup\JetPages\Middleware;

use Closure;
use Illuminate\Http\Request;
use ShvetsGroup\JetPages\PageBuilder\PageCache;
use Symfony\Component\HttpFoundation\Response;

class StaticCache
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  null  $cache_bag
     * @return mixed
     */
    public function handle($request, Closure $next, $cache_bag = null)
    {
        /* @var $response Response */
        $response = $next($request);

        $cache_bag = $request->server('cache_bag', $cache_bag);
        $force = $request->server('force_static_cache', false);

        $pageCache = new PageCache();
        $pageCache->handleRequest($request, $response, $cache_bag, $force);

        return $response;
    }
}
