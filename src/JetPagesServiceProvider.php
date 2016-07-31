<?php namespace ShvetsGroup\JetPages;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Routing\Router;
use ShvetsGroup\JetPages\Builders\Decorators\MetaInfoDecorator;
use ShvetsGroup\JetPages\Builders\Scanners\PageScanner;

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

        $this->app->bind(Page\Page::class, function($app, $parameters){
            $driver = config('jetpages.driver', 'cache');
            switch ($driver) {
                case "cache":
                    return $this->app->make(Page\CachePage::class, $parameters);
                    break;
                case "database":
                    return $this->app->make(Page\EloquentPage::class, $parameters);
                    break;
                default:
                    throw new \Exception("Unknown pages driver '{$driver}'.");
            }
        });
        $this->app->alias(Page\Page::class, 'page');

        $this->app->bind(Page\PageRegistry::class, function($app, $parameters){
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
        $this->app->alias(Page\PageRegistry::class, 'pages');

        $this->app->singleton('command.jetpages.build', function () {
            return new Commands\Build(
                $this->getDefaultScanners(),
                $this->getDefaultDecorators()
            );
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
        $this->publishes([__DIR__ . '/resources/views' => base_path('resources/views/vendor/sg/jetpages')], 'views');
        $this->publishes([__DIR__ . '/resources/migrations/' => database_path('/migrations')], 'migrations');
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

    protected function getDefaultScanners()
    {
        return config('jetpages.scanners', [PageScanner::class => [content_path('pages')]]);
    }

    protected function getDefaultDecorators()
    {
        return config('jetpages.decorators', [MetaInfoDecorator::class]);
    }
}

if (! function_exists('content_path')) {
    /**
     * Get the content path.
     *
     * @param  string  $path
     * @return string
     */
    function content_path($path = '')
    {
        return config('jetpages.content_dir', resource_path('content')).($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}
