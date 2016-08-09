<?php namespace ShvetsGroup\JetPages\Middleware;

use Closure;
use File;
use Auth;
use App;

class StaticCache
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /* @var $response \Symfony\Component\HttpFoundation\Response */
        $response = $next($request);

        /* @var $staticCache \ShvetsGroup\JetPages\Builders\StaticCache */
        $staticCache = app('jetpages.staticCache');
        $staticCache->handleRequest($request, $response);


        return $response;
    }

}
