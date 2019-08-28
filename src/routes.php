<?php

use ShvetsGroup\JetPages\Page\PageUtils;

$router = app('router');
$configSupportedLocales = config('laravellocalization.supportedLocales');
$configLocaleDomains = config('laravellocalization.localeDomains');

if (!function_exists('declare_catch_all_route')) {
    function declare_catch_all_route($router)
    {
        // Specific override for a front page to overcome default laravel's route in app/Http/routes.php
        $router->middleware('static-cache')->get('/', 'ShvetsGroup\JetPages\Controllers\PageController@show');

        $exceptions = [];

        if ($nova = config('nova.path', '')) {
            $nova = trim($nova, '/');
            $exceptions[] = $nova . '$';
            $exceptions[] = $nova . '/';
            $exceptions[] = 'nova-api/';
        }

        $exceptions = $exceptions ? '(?!' . implode('|', $exceptions) . ')' : '';

        $router->middleware('static-cache')->get('{all}', 'ShvetsGroup\JetPages\Controllers\PageController@show')->where(['all' => '^' . $exceptions . '.*$']);
    }
}

// Add these routes after bootstrap is done in order to make them last in
// the route list. Otherwise, catch-all route will break some other
// routes registered after it.
app()->booted(function () use ($router, $configSupportedLocales, $configLocaleDomains) {

    $routesFile = storage_path('app/routes/routes.json');
    if (!file_exists($routesFile)) {
        return declare_catch_all_route($router);
    }

    $redirectsFile = storage_path('app/redirects/redirects.json');
    if (!file_exists($redirectsFile)) {
        return declare_catch_all_route($router);
    }

    $router->group(['namespace' => 'ShvetsGroup\JetPages\Controllers'], function () use ($redirectsFile, $router, $configSupportedLocales, $configLocaleDomains) {
        $localeDomains = [];

        $redirects = json_decode(file_get_contents($redirectsFile), true);

        foreach ($redirects as $from => $to) {

            list($locale, $slug) = PageUtils::uriToLocaleSlugArray($from);
            $localeSlug = PageUtils::makeLocaleSlug($locale, $slug);
            $from = PageUtils::makeUri($locale, $slug);
            $fromFull = PageUtils::absoluteUrl($from, $locale);

            if (!starts_with($to, 'http://') && !starts_with($to, 'https://')) {
                list($locale, $slug) = PageUtils::uriToLocaleSlugArray($to);
                $toFull = PageUtils::absoluteUrl(PageUtils::makeUri($locale, $slug), $locale);
            } else {
                $toFull = $to;
            }

            $routeData = [
                'uses' => 'PageController@redirect',
                'name' => $localeSlug,
            ];

            if ($configSupportedLocales) {
                $routeData['middleware'] = 'set_locale:' . $locale;
            }

            if ($configSupportedLocales) {
                if (!isset($localeDomains[$locale])) {
                    $localeDomains[$locale] = PageUtils::getLocaleDomain($locale);
                }
                $domain = $localeDomains[$locale];
                $routeData['domain'] = $domain;
            }

            $router->get($from, $routeData)
                ->defaults('from', $fromFull)
                ->defaults('to', $toFull);
        }
    });

    $router->group(['namespace' => 'ShvetsGroup\JetPages\Controllers', 'middleware' => 'static-cache'], function () use ($routesFile, $router, $configSupportedLocales, $configLocaleDomains) {
        $localeDomains = [];

        $routes = json_decode(file_get_contents($routesFile), true);
        foreach ($routes as $r) {
            list($locale, $uri) = explode(':', $r, 2);
            list(, $slug) = PageUtils::uriToLocaleSlugArray($uri);
            $localeSlug = PageUtils::makeLocaleSlug($locale, $slug);

            $routeData = [
                'uses' => 'PageController@show',
                'name' => $localeSlug,
            ];

            if ($configSupportedLocales) {
                $routeData['middleware'] = 'set_locale:' . $locale;
            }

            if ($configSupportedLocales) {
                if (!isset($localeDomains[$locale])) {
                    $localeDomains[$locale] = PageUtils::getLocaleDomain($locale);
                }
                $domain = $localeDomains[$locale];
                $routeData['domain'] = $domain;
            }

            $router->get($uri, $routeData);
        }
    });
});

$router->namespace('ShvetsGroup\JetPages\Controllers')->group(function () use ($router) {
    $router->get('ajax/jetpages/timestamp', 'PageController@getContentTimestamp');

    $router->group(['middleware' => 'static-cache'], function () use ($router) {
        $router->get('robots.txt', 'RobotsTxtController@robots');
        $router->get('sitemap.xml', 'SiteMapController@sitemap');
    });
});
