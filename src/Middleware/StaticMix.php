<?php

namespace ShvetsGroup\JetPages\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use ShvetsGroup\JetPages\PageBuilder\PageImageMix;

class StaticMix
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  null  $cache_bag
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /* @var $response Response */
        $response = $next($request);

        $pageCache = new PageImageMix();
        $pageCache->handleRequest($request, $response);

        return $response;
    }
}
