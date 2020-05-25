<?php

namespace ShvetsGroup\JetPages\Controllers;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use ReflectionObject;
use ShvetsGroup\JetPages\Page\PageQuery;
use ShvetsGroup\JetPages\Page\PageUtils;
use ShvetsGroup\JetPages\PageBuilder\PageBuilder;
use ShvetsGroup\JetPages\PageBuilder\PostProcessors\RedirectsPostProcessor;

class PageController extends Controller
{
    /**
     * @var Store
     */
    protected $cache;

    /**
     * @var PageBuilder
     */
    protected $builder;

    /**
     * @var PageUtils
     */
    protected $pageUtils;

    protected $redirectCacheFile;

    protected $routesCacheFile;

    public function __construct()
    {
        $this->cache = app('cache.store');
        $this->builder = app('page.builder');
        $this->pageUtils = app('page.utils');

        $this->redirectCacheFile = storage_path(RedirectsPostProcessor::REDIRECT_CACHE_PATH);
        $this->routesCacheFile = storage_path(PageBuilder::ROUTES_CACHE_PATH);
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $uri
     * @param  Request  $request
     * @return RedirectResponse|Redirector|Response|void
     */
    public function show(Request $request, $uri = '')
    {
        list($uri, $locale, $slug) = $this->getUriLocaleSlug($request, $uri);

        if ($redirect = $this->getRedirect($uri, $locale)) {
            return redirect($redirect, 301);
        }

        $this->setAppLocale($locale);

        if ($this->isUnknownPageRoute($locale, $uri)) {
            return abort(404);
        }

        // 3. If routes file is not loaded or if page is found, seek the page in DB.
        $page = PageQuery::findBySlug($locale, $slug);

        if ($page && $rebuildOnEachView = config('jetpages.rebuild_page_on_view', config('app.debug', false))) {
            $this->builder->forcePagesToRebuild([$this->pageUtils->makeLocaleSlug($locale, $slug)]);
            $this->builder->build();
            $page = PageQuery::findBySlug($locale, $slug);
        }

        if (!$page || $page->isPrivate()) {
            return abort(404);
        }

        $html = $page->render();
        $response = response()->make($html);

        $pageCache = $page->getAttribute('cache');
        request()->route()->setParameter('cache', $pageCache);
        if ($pageCache) {
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

    protected function getUriLocaleSlug(Request $request, $uri)
    {
        $uri = $uri ?: $request->path();

        $fullUrl = $this->pageUtils->getBaseUrl().ltrim($uri, '/');
        list($locale, $_uri) = $this->pageUtils->extractLocaleFromURL($fullUrl);

        $slug = $this->pageUtils->uriToSlug($_uri);

        return [$uri, $locale, $slug];
    }

    protected function getRedirect($uri, $locale)
    {
        $redirects = $this->cache->get('jetpages:redirects');

        $uri = $this->pageUtils->toMainDomainUri($uri, $locale);

        if ($redirects === null) {
            if (file_exists($this->redirectCacheFile)) {
                $redirects = json_decode(file_get_contents($this->redirectCacheFile), true);
            }
            if (!is_array($redirects)) {
                $redirects = [];
            }
            $this->cache->forever('jetpages:redirects', $redirects);
        }

        return $redirects[$uri] ?? null;
    }

    protected function isUnknownPageRoute($locale, $uri)
    {
        $routes = $this->cache->get('jetpages:routes');

        if ($routes === null) {
            if (file_exists($this->routesCacheFile)) {
                $routes = json_decode(file_get_contents($this->routesCacheFile), true);
                $this->cache->forever('jetpages:routes', $routes);
            }
        }

        return is_array($routes) && (!isset($routes[$locale.':'.$uri]) || !$routes[$locale.':'.$uri]);
    }

    protected function setAppLocale(string $locale)
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
}
