<?php

namespace ShvetsGroup\JetPages\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ShvetsGroup\JetPages\Page\PageRegistry;
use ShvetsGroup\JetPages\Page\PageUtils;

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
     *
     * @param string $uri
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $uri = null)
    {
        $uri = $uri ?: $request->path();

        $fullUrl = PageUtils::getBaseUrl() . ltrim($uri, '/');

        list($locale, $_uri) = PageUtils::extractLocaleFromURL($fullUrl);
        $slug = PageUtils::uriToSlug($_uri);

        $this->setLocale($locale);

        $page = $this->pages->findBySlug($locale, $slug);

        $rebuildOnEachView = config('jetpages.rebuild_page_on_view', config('app.debug', false));
        if ($rebuildOnEachView) {
            app('builder')->build(false, $page ? $page->localeSlug() : []);
            $page = $this->pages->findBySlug($locale, $slug);
        }

        if (!$page || $page->isPrivate()) {
            $redirectsFile = storage_path('app/redirects/redirects.json');
            if (file_exists($redirectsFile)) {
                $redirects = json_decode(file_get_contents($redirectsFile), true);
            }
            else {
                $redirects = [];
            }

            if (isset($redirects[$uri])) {
                return redirect($redirects[$uri], 301);
            } else {
                return abort(404);
            }
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
     * @param null $from
     * @param null $to
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function redirect($from, $to) {
        return redirect($to, 301);
    }

    /**
     * Set app locale.
     *
     * @param $locale
     * @throws \ReflectionException
     */
    public function setLocale($locale) {
        if (!app()->bound('laravellocalization')) {
            app()->setLocale($locale);
            return;
        }

        $localization = app('laravellocalization');

        $localeDomains = config('laravellocalization.localeDomains');

        if (!$localeDomains) {
            return $localization->setLocale($locale);
        }

        $domain = PageUtils::getHost();
        $localesOnThisDomain = array_wrap(array_get($localeDomains, $domain, array_get($localeDomains, '')));
        if (!$localesOnThisDomain) {
            throw new \Exception("Can not determine locale configuration on this domain.");
        }

        $defaultLocale = reset($localesOnThisDomain);
        $r = new \ReflectionObject($localization);
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContentTimestamp()
    {
        $date = $this->pages->lastBuildTime() ?: $this->pages->lastUpdatedTime();

        return response()->json([
            'timestamp' => $date ? strtotime($date) : 0
        ], 200, [], JSON_NUMERIC_CHECK)
            ->header('Cache-Control', 'no-cache')
            ->header('Content-Type', 'application/json');
    }
}
