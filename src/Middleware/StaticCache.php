<?php

namespace ShvetsGroup\JetPages\Middleware;

use App;
use Auth;
use Closure;
use File;
use Illuminate\Http\Request;
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

        /* @var $staticCache \ShvetsGroup\JetPages\Builders\StaticCache */
        $staticCache = app('jetpages.staticCache');
        $result = $staticCache->handleRequest($request, $response);

        return $response;
    }

}
