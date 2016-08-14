<?php namespace ShvetsGroup\JetPages;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Routing\Router;
use ShvetsGroup\JetPages\Builders\BaseBuilder;
use ShvetsGroup\JetPages\Builders\Outline;
use ShvetsGroup\JetPages\Builders\StaticCache;
use ShvetsGroup\JetPages\Middleware\StaticCache as StaticCacheMiddleware;

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

        // We set this to know the default locale later, since Laravel might change the original value.
        config()->set('app.default_locale', config('app.locale', ''));

        $this->app->bind('page', Page\Page::class);
        $this->app->singleton('pages', function ($app, $parameters) {
            $driver = config('jetpages.driver', 'cache');
            switch ($driver) {
                case "cache":
                    return $this->app->make(Page\CachePageRegistry::class, $parameters);
                    break;
                case "database":
                    return $this->app->make(Page\EloquentPageRegistry::class, $parameters);
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
            return new BaseBuilder(
                $this->getDefaultScanners(),
                $this->getDefaultParsers(),
                $this->getDefaultRenderers(),
                $this->getDefaultPostProcessors()
            );
        });
        $this->app->alias('builder', BaseBuilder::class);

        $this->app->singleton('command.jetpages.build', function () {
            return new Commands\Build();
        });
        $this->commands(['command.jetpages.build']);
    }

    /**
     * Bootstrap the application services.
     *
     * @param Router $router
     */
    public function boot(Router $router)
    {
        parent::boot($router);

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'sg/jetpages');
        view()->composer('*', 'ShvetsGroup\JetPages\ViewComposers\LocaleComposer');
        view()->composer('*', 'ShvetsGroup\JetPages\ViewComposers\MenuComposer');

        $this->publishes([__DIR__ . '/resources/views' => base_path('resources/views/vendor/sg/jetpages')], 'views');
        $this->publishes([__DIR__ . '/resources/migrations/' => database_path('/migrations')], 'migrations');
        $this->publishes([__DIR__ . '/resources/config/jetpages.php' => config_path('jetpages.php')], 'config');

        $router->middleware('static-cache', StaticCacheMiddleware::class);
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
        $router->group(['namespace' => __NAMESPACE__ . '\Controllers', 'middleware' => 'web'], function () {
            require __DIR__ . '/routes.php';
        });
    }

    /**
     * Get default scanner config.
     *
     * @return mixed
     */
    protected function getDefaultScanners()
    {
        return config('jetpages.content_scanners', ['pages']);
    }

    /**
     * Get default decorator config.
     *
     * @return mixed
     */
    protected function getDefaultParsers()
    {
        return config('jetpages.content_parsers', [
            '\ShvetsGroup\JetPages\Builders\Parsers\MetaInfoParser',
            '\ShvetsGroup\JetPages\Builders\Parsers\NavigationParser',
            '\ShvetsGroup\JetPages\Builders\Parsers\BreadcrumbParser',
        ]);
    }

    /**
     * Get default decorator config.
     *
     * @return mixed
     */
    protected function getDefaultRenderers()
    {
        return config('jetpages.content_renderers', [
            '\ShvetsGroup\JetPages\Builders\Renderers\IncludeRenderer',
            '\ShvetsGroup\JetPages\Builders\Renderers\MarkdownRenderer',
            '\ShvetsGroup\JetPages\Builders\Renderers\EscapePreTagRenderer',
        ]);
    }

    /**
     * Get default decorator config.
     *
     * @return mixed
     */
    protected function getDefaultPostProcessors()
    {
        return config('jetpages.content_post_processors', [
            '\ShvetsGroup\JetPages\Builders\PostProcessors\MenuPostProcessor',
            '\ShvetsGroup\JetPages\Builders\PostProcessors\RedirectsPostProcessor',
            '\ShvetsGroup\JetPages\Builders\PostProcessors\StaticCachePostProcessor',
        ]);
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
        return config('jetpages.content_root', resource_path('content')) . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}
