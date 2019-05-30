<?php namespace ShvetsGroup\JetPages\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ShvetsGroup\JetPages\Page\PageRegistry;
use ShvetsGroup\JetPages\Page\Page;
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
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function show($uri = '/', Request $request)
    {
        list($locale, $slug) = $this->extractLocaleSlug($request->url());

        $this->setLocale($locale);

        $page = $this->pages->findBySlug($locale, $slug);

        $rebuildOnEachView = config('jetpages.rebuild_page_on_view', config('app.debug', false));
        if ($rebuildOnEachView) {
            app('builder')->build(false, $page ? $page->localeSlug() : []);
            $page = $this->pages->findBySlug($locale, $slug);
        }

        if (!$page || $page->isPrivate()) {
            if ($destination = Cache::get("redirect:{$uri}")) {
                return redirect($destination, 301);
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
     * If LaravelLocalization package installed, then make sure that uri contains the locale.
     *
     * @param $url
     * @return array [$locale, $slug]
     */
    public function extractLocaleSlug($url)
    {
        $hasLocalization = app()->bound('laravellocalization') && $localization = app('laravellocalization');

        if (!$hasLocalization) {
            $locale = app()->getLocale();
            $slug = Page::uriToSlug(ltrim(parse_url($url)['path'], '/'));
            return [$locale, $slug];
        }

        $hasLocaleDomainsConfigured = config('laravellocalization.localeDomains');

        if (!$hasLocaleDomainsConfigured) {
            $locale = app()->getLocale();
            $slug = Page::uriToSlug(ltrim(parse_url($url)['path'], '/'));
            return [$locale, $slug];
        }


        $localeDomains = config('laravellocalization.localeDomains');
        $hideDefaultLocaleInUrl = config('laravellocalization.hideDefaultLocaleInURL');

        $parts = parse_url($url);
        $domain = $parts['host'];

        $localesOnThisDomain = array_wrap(array_get($localeDomains, $domain));
        if (!$localesOnThisDomain) {
            throw new \Exception("Can not determine locale configuration on this domain.");
        }

        $segments = explode('/', ltrim($parts['path'] ?? '', '/'));

        if (in_array($segments[0], $localesOnThisDomain)) {
            $locale = $localesOnThisDomain;
            array_shift($segments);
            $slug = Page::uriToSlug(implode('/', $segments));
            return [$locale, $slug];
        }
        else if ($hideDefaultLocaleInUrl) {
            $locale = reset($localesOnThisDomain);
            $slug = Page::uriToSlug(implode('/', $segments));
            return [$locale, $slug];
        }
        else {
            throw new \Exception("Locale should be present in the url, but it's not.");
        }
    }

    public function setLocale($locale) {
        $localization = app('laravellocalization');

        $localeDomains = config('laravellocalization.localeDomains');

        if (!$localeDomains) {
            return $localization->setLocale($locale);
        }

        $domain = request()->getHost();
        $localesOnThisDomain = array_wrap(array_get($localeDomains, $domain));
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
        foreach ($supportedLocales as $locale => $data) {
            if (!in_array($locale, $localesOnThisDomain)) {
                unset($supportedLocales[$locale]);
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
