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
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /* @var $response Response */
        $response = $next($request);

        $pageCache = new PageCache();
        $pageCache->handleRequest($request, $response);

        return $response;
    }

}
