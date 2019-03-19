<?php namespace ShvetsGroup\JetPages\Controllers;

use Illuminate\Routing\Controller;
use ShvetsGroup\JetPages\Page\PageRegistry;
use Cache;

class PageController extends Controller
{
    /**
     * @var PageRegistry
     */
    private $pages;

    public function __construct()
    {
        $this->pages = app('pages');
    }

    /**
     * Display the specified resource.
     * @param string $uri
     * @return \Illuminate\Http\Response
     */
    public function show($uri = '/')
    {
        $this->processLocale($uri);

        $page = $this->pages->findByUri($uri);

        $debug = config('jetpages.rebuild_page_on_view', config('app.debug', false));

        if ($debug) {
            app('builder')->build(false, $page ? $page->localeSlug() : []);
            $page = $this->pages->findByUri($uri);
        }

        if (!$page || $page->isPrivate()) {
            if ($destination = Cache::get("redirect:{$uri}")) {
                return redirect($destination, 301);
            } else {
                return abort(404);
            }
        }

        $response = response()->make($page->render($debug));

        $cache = $page->getAttribute('cache', true);
        request()->route()->setParameter('cache', $cache);

        if ($cache) {
            $response->setPublic()->setMaxAge(60 * 5)->setSharedMaxAge(3600 * 24 * 365);
        }

        return $response;
    }

    /**
     * If LaravelLocalization package installed, then make sure that uri contains the locale.
     *
     * @param $uri
     * @return string
     */
    public function processLocale($uri)
    {
        if (app()->bound('laravellocalization') && $localization = app('laravellocalization')) {
            $localization->setLocale(null) ?: $localization->getCurrentLocale();
        }
    }

    /**
     * This timestamp can be used to invalidate local client content cache.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContentTimestamp()
    {
        $lastBuild = $this->pages->lastBuildTime();
        $lastUpdated = $this->pages->lastUpdatedTime();
        $date = $lastBuild ?: $lastUpdated;

        return response()->json([
            'timestamp' => $date ? strtotime($date) : 0
        ], 200, [], JSON_NUMERIC_CHECK)
            ->header('Cache-Control', 'no-cache')
            ->header('Content-Type', 'application/json');
    }
}
