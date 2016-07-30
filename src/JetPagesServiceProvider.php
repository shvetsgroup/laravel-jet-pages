<?php namespace ShvetsGroup\JetPages;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Routing\Router;
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

        $driver = config('jetpages.driver', 'cache');
        switch ($driver) {
            case "cache":
                $this->app->bind(Page\Pagelike::class, Page\CachePage::class);
                break;
            case "database":
                $this->app->bind(Page\Pagelike::class, Page\EloquentPage::class);
                break;
            default:
                throw new \Exception("Unknown pages driver '{$driver}'.");
        }
        $this->app->alias(Page\Pagelike::class, 'page');

        $this->app->singleton('command.jetpages.build', function ($app) {
            return new Commands\Build($this->getDefaultScanners());
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
