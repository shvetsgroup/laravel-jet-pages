<?php namespace ShvetsGroup\JetPages\Controllers;

use Illuminate\Routing\Controller;
use ShvetsGroup\JetPages\Page\PageRegistry;

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
        $uri = $this->processLocale($uri);

        if (config('jetpages.rebuild_page_on_view', env('APP_DEBUG', false))) {
            $page = app('builder')->reBuild($uri);
        }
        else {
            $page = $this->pages->findByUriOrFail($uri);
        }

        $view = array_get($page, 'view', 'page');
        foreach ([$view, "sg/jetpages::$view"] as $v) {
            if (view()->exists($v)) {
                $view = $v;
            }
        }
        return response()->view($view, $page->toArray());
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
            $locale = $localization->setLocale(null) ?: $localization->getCurrentLocale();
            $uri = $locale . '/' . ltrim(preg_replace('|^' . preg_quote($locale, '|') . '|', '', $uri), '/');
        }
        return $uri;
    }

    /**
     * This timestamp can be used to invalidate local client content cache.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContentTimestamp()
    {
        return response()->json([
            'timestamp' => $this->pages->lastUpdatedTime()
        ]);
    }
}
