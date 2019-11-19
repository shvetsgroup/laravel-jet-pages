<?php

namespace ShvetsGroup\JetPages\Controllers;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use ReflectionException;
use ReflectionObject;
use ShvetsGroup\JetPages\Page\PageRegistry;
use ShvetsGroup\JetPages\Page\PageUtils;

class PageController extends Controller
{
    /**
     * @var PageUtils
     */
    private $pageUtils;

    /**
     * @var PageRegistry
     */
    private $pages;

    /**
     * @var Store
     */
    private $cache;

    public function __construct()
    {
        $this->pageUtils = app('page.utils');
        $this->pages = app('pages');
        $this->cache = app('cache.store');
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $uri
     * @param  Request  $request
     * @return Response
     */
    public function show(Request $request, $uri = null)
    {
        $uri = $uri ?: $request->path();
        $fullUrl = $this->pageUtils->getBaseUrl().ltrim($uri, '/');
        list($locale, $_uri) = $this->pageUtils->extractLocaleFromURL($fullUrl);
        $slug = $this->pageUtils->uriToSlug($_uri);
        $this->setLocale($locale);

        // 1. Try to find a suitable redirect.
        $redirects = $this->cache->get('jetpages:redirects');
        if ($redirects === null) {
            $redirectsFile = storage_path('app/redirects/redirects.json');
            if (file_exists($redirectsFile)) {
                $redirects = json_decode(file_get_contents($redirectsFile), true);
            } else {
                $redirects = [];
            }
            $this->cache->forever('jetpages:redirects', $redirects);
        }
        if (is_array($redirects) && isset($redirects[$uri])) {
            return redirect($redirects[$uri], 301);
        }

        // 2. Then try to see if the page exists in the routes file quickly.
        $routes = $this->cache->get('jetpages:routes');
        if ($routes === null) {
            $routesFile = storage_path('app/routes/routes.json');
            if (file_exists($routesFile)) {
                $routes = json_decode(file_get_contents($routesFile), true);
                $this->cache->forever('jetpages:routes', $routes);
            }
        }
        if (is_array($routes) && (!isset($routes[$locale.':'.$uri]) || !$routes[$locale.':'.$uri])) {
            return abort(404);
        }

        // 3. If routes file is not loaded or if page is found, seek the page in DB.
        $page = $this->pages->findBySlug($locale, $slug);

        if ($page && $rebuildOnEachView = config('jetpages.rebuild_page_on_view', config('app.debug', false))) {
            app('builder')->build(false, $this->pageUtils->makeLocaleSlug($locale, $slug));
            $page = $this->pages->findBySlug($locale, $slug);
        }

        if (!$page || $page->isPrivate()) {
            return abort(404);
        }

        $html = $page->render($rebuildOnEachView);
        $response = response()->make($html);

        $cache = $page->getAttribute('cache', true);
        request()->route()->setParameter('cache', $cache);

        if ($cache) {
            $response->setPublic()->setMaxAge(60 * 5)->setSharedMaxAge(3600 * 24 * 365);
        }

        return $response;
    }

    /**
     * Redirect route.
     *
     * @param  null  $from
     * @param  null  $to
     * @return RedirectResponse|Redirector
     */
    public function redirect($from, $to)
    {
        return redirect($to, 301);
    }

    /**
     * Set app locale.
     *
     * @param $locale
     * @throws ReflectionException
     */
    public function setLocale($locale)
    {
        if (!app()->bound('laravellocalization')) {
            app()->setLocale($locale);
            return;
        }

        $localization = app('laravellocalization');

        $localeDomains = config('sg.localeDomains');
        if (!$localeDomains) {
            return $localization->setLocale($locale);
        }
        $localesOnThisDomain = $this->pageUtils->getLocalesOnDomain();

        $defaultLocale = reset($localesOnThisDomain);
        $r = new ReflectionObject($localization);
        $p = $r->getProperty('defaultLocale');
        $p->setAccessible(true);
        $p->setValue($localization, $defaultLocale);

        $supportedLocales = config('laravellocalization.supportedLocales');
        if (empty($supportedLocales) || !is_array($supportedLocales)) {
            $supportedLocales[$defaultLocale] = ['name' => 'English', 'native' => 'English'];
        }
        foreach ($supportedLocales as $l => $data) {
            if (!in_array($locale, $localesOnThisDomain)) {
                unset($supportedLocales[$l]);
            }
        }

        $originalSupportedLocales = $localization->getSupportedLocales();
        $localization->setSupportedLocales($supportedLocales);
        $localization->setLocale($locale);
        if ($originalSupportedLocales) {
            $localization->setSupportedLocales($originalSupportedLocales);
        }
    }

    /**
     * This timestamp can be used to invalidate local client content cache.
     *
     * @return JsonResponse
     */
    public function getContentTimestamp()
    {
        $date = $this->pages->lastBuildTime() ?: $this->pages->lastUpdatedTime();

        return response()->json([
            'timestamp' => $date ? strtotime($date) : 0,
        ], 200, [], JSON_NUMERIC_CHECK)
            ->header('Cache-Control', 'no-cache')
            ->header('Content-Type', 'application/json');
    }
}
