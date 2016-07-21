<?php namespace ShvetsGroup\JetPages;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;

class JetPagesServiceProvider extends RouteServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
        $this->app->bind(Page\Pageable::class, Page\EloquentPage::class);
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
        $this->publishes([__DIR__ . '/migrations/' => database_path('/migrations'),], 'migrations');
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
}