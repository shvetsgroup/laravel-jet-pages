<?php namespace ShvetsGroup\JetPages;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Routing\Router;
use ShvetsGroup\JetPages\Builders\BaseBuilder;
use ShvetsGroup\JetPages\Builders\Outline;

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

        $this->app->bind('page', function($app, $parameters){
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
        $this->app->alias('page', Page\Page::class);

        $this->app->bind('pages', function($app, $parameters){
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

        $this->app->singleton('outline', function () {
            return new Outline();
        });
        $this->app->singleton('builder', function () {
            return new BaseBuilder(
                $this->getDefaultScanners(),
                $this->getDefaultDecorators()
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
    protected function getDefaultDecorators()
    {
        // TODO: update decorators
        return config('jetpages.content_decorators', [
            '\ShvetsGroup\JetPages\Builders\Decorators\LocaleDecorator',
            '\ShvetsGroup\JetPages\Builders\Decorators\MetaInfoDecorator',
            '\ShvetsGroup\JetPages\Builders\Decorators\NavigationDecorator',
            '\ShvetsGroup\JetPages\Builders\Decorators\MenuDecorator',
            '\ShvetsGroup\JetPages\Builders\Decorators\Content\IncludeDecorator',
            '\ShvetsGroup\JetPages\Builders\Decorators\Content\MarkdownDecorator',
            '\ShvetsGroup\JetPages\Builders\Decorators\Content\EscapePreTagDecorator',
        ]);
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
        return config('jetpages.content_root', resource_path('content')).($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}
