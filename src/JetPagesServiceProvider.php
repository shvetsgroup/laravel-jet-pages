<?php

namespace ShvetsGroup\JetPages;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Routing\Router;
use ShvetsGroup\JetPages\Builders\BaseBuilder;
use ShvetsGroup\JetPages\Builders\Outline;
use ShvetsGroup\JetPages\Builders\StaticCache;
use ShvetsGroup\JetPages\Middleware\StaticCache as StaticCacheMiddleware;
use ShvetsGroup\JetPages\Page\PageUtils;

class JetPagesServiceProvider extends RouteServiceProvider
{
    /**
     * Register the service provider.
     *
     * @throws \Exception
     */
    public function register()
    {
        parent::register();

        // We set this to know the default locale later, since Laravel might
        //change the original value.
        config()->set('app.default_locale', config('app.locale', ''));

        $this->app->bind('page', Page\PageUtils::class);
        $this->app->singleton('pages', function ($app) {
            $driver = config('jetpages.driver', 'cache');
            switch ($driver) {
                case "cache":
                    return $this->app->make(Page\CachePageRegistry::class);
                    break;
                case "database":
                    return $this->app->make(Page\EloquentPageRegistry::class);
                    break;
                default:
                    throw new \Exception("Unknown pages driver '{$driver}'.");
            }
        });
        $this->app->alias('pages', Page\PageRegistry::class);

        $this->app->singleton('jetpages.outline', function () {
            return new Outline();
        });
        $this->app->singleton('jetpages.staticCache', function () {
            return new StaticCache();
        });
        $this->app->singleton('builder', function () {
            return new BaseBuilder();
        });
        $this->app->alias('builder', BaseBuilder::class);

        $this->commands([
            Commands\Build::class,
            Commands\Cache::class,
        ]);
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        parent::boot();

        $this->loadMigrationsFrom(__DIR__ . '/resources/migrations');

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'sg/jetpages');
        $this->app['view']->composer('*', 'ShvetsGroup\JetPages\ViewComposers\LocaleComposer');
        $this->app['view']->composer('*', 'ShvetsGroup\JetPages\ViewComposers\MenuComposer');

        $this->publishes([__DIR__ . '/resources/views' => base_path('resources/views/vendor/sg/jetpages')], 'views');

        $this->mergeConfigFrom(__DIR__ . '/resources/config/jetpages.php', 'jetpages');
        $this->publishes([__DIR__ . '/resources/config/jetpages.php' => config_path('jetpages.php')], 'config');

        $this->app['router']->aliasMiddleware('static-cache', StaticCacheMiddleware::class);
    }

    /**
     * Define the routes for the application.
     *
     * @param  Router $router
     *
     * @return void
     */
    public function map(Router $router)
    {
        $router->group(['namespace' => __NAMESPACE__ . '\Controllers'], function () use ($router) {
            $router->get('ajax/jetpages/timestamp', 'PageController@getContentTimestamp');

            $router->group(['middleware' => ['static-cache']], function () use ($router) {
                $router->get('robots.txt', 'RobotsTxtController@robots');
                $router->get('sitemap.xml', 'SiteMapController@sitemap');
            });
        });

        // Add these routes after bootstrap is done in order to make them last in
        // the route list. Otherwise, catch-all route will break some other
        // routes registered after it.
        $this->app->booted(function () use ($router) {

            $router->group(['namespace' => __NAMESPACE__ . '\Controllers'], function () use ($router) {

                $redirectsFile = storage_path('app/redirects/redirects.json');
                if (!file_exists($redirectsFile)) {
                    return;
                }

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
                    }
                    else {
                        $toFull = $to;
                    }

                    $routeData = [
                        'uses' => 'PageController@redirect',
                        'name' => $localeSlug,
                    ];

                    if (config('laravellocalization.supportedLocales')) {
                        $routeData['middleware'] = 'set_locale:' . $locale;
                    }

                    if (config('laravellocalization.localeDomains')) {
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

            $router->group(['namespace' => __NAMESPACE__ . '\Controllers', 'middleware' => ['static-cache']], function () use ($router) {

                $routesFile = storage_path('app/routes/routes.json');
                if (file_exists($routesFile)) {
                    $routes = json_decode(file_get_contents($routesFile), true);
                }
                
                if (!empty($routes)) {

                    $localeDomains = [];
                    foreach ($routes as $r) {
                        list($locale, $uri) = explode(':', $r, 2);
                        list(,$slug) = PageUtils::uriToLocaleSlugArray($uri);
                        $localeSlug = PageUtils::makeLocaleSlug($locale, $slug);

                        $routeData = [
                            'uses' => 'PageController@show',
                            'name' => $localeSlug,
                        ];

                        if (config('laravellocalization.supportedLocales')) {
                            $routeData['middleware'] = 'set_locale:' . $locale;
                        }

                        if (config('laravellocalization.localeDomains')) {
                            if (!isset($localeDomains[$locale])) {
                                $localeDomains[$locale] = PageUtils::getLocaleDomain($locale);
                            }
                            $domain = $localeDomains[$locale];
                            $routeData['domain'] = $domain;
                        }

                        $router->get($uri, $routeData);
                    }

                }
                else {

                    // Specific override for a front page to overcome default laravel's route in app/Http/routes.php
                    $router->get('/', 'PageController@show');

                    $exceptions = [];

                    if ($nova = config('nova.path', '')) {
                        $nova = trim($nova, '/');
                        $exceptions[] = $nova . '$';
                        $exceptions[] = $nova . '/';
                        $exceptions[] = 'nova-api/';
                    }

                    $exceptions = $exceptions ? '(?!' . implode('|', $exceptions). ')' : '';

                    $router->get('{all}', 'PageController@show')->where(['all' => '^' . $exceptions . '.*$']);
                }
            });
        });
    }
}

if (!function_exists('content_path')) {
    /**
     * Get the content path.
     *
     * @param  string $path
     * @return string
     */
    function content_path($path = '')
    {
        return base_path(config('jetpages.content_root', 'resources/content') . ($path ? DIRECTORY_SEPARATOR . $path : $path));
    }
}
