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

        if (!$page) {
            if ($destination = Cache::get("redirect:{$uri}")) {
                return redirect($destination, 301);
            } else {
                return abort(404);
            }
        }

        if (config('jetpages.rebuild_page_on_view', env('APP_DEBUG', false))) {
            app('builder')->build(false, $uri);
            $page = $this->pages->findByUriOrFail($uri);
        }

        request()->route()->setParameter('cache', $page->getAttribute('cache', true));

        return $page->render();
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

        return response()->json([
            'timestamp' => $lastBuild ?: $lastUpdated
        ], 200, [], JSON_NUMERIC_CHECK)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    }
}
