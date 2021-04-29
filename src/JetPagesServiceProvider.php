<?php

namespace ShvetsGroup\JetPages;

use Exception;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use ShvetsGroup\JetPages\Middleware\StaticCache;
use ShvetsGroup\JetPages\PageBuilder\PageBuilder;
use ShvetsGroup\JetPages\PageBuilder\PageMenu;
use ShvetsGroup\JetPages\PageBuilder\PageOutline;

class JetPagesServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @throws Exception
     */
    public function register()
    {
        // We set this to know the default locale later, since Laravel might
        // change the original value.
        config()->set('app.default_locale', config('app.locale', ''));

        $loader = AliasLoader::getInstance();

        $this->app->singleton('page.utils', Page\PageUtils::class);
        $loader->alias('PageUtils', Facades\PageUtils::class);

        $this->app->singleton('page.outline', function () {
            return new PageOutline();
        });
        $this->app->singleton('page.builder', function () {
            return new PageBuilder();
        });
        $this->app->singleton('page.menu', function () {
            return new PageMenu();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\Build::class,
                Commands\Cache::class,
            ]);
        }
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/resources/migrations');

        $this->mergeConfigFrom(__DIR__.'/resources/config/jetpages.php', 'jetpages');
        $this->publishes([__DIR__.'/resources/config/jetpages.php' => config_path('jetpages.php')], 'config');

        $this->loadViewsFrom(__DIR__.'/resources/views', 'sg/jetpages');
        $this->publishes([__DIR__.'/resources/views' => base_path('resources/views/vendor/sg/jetpages')], 'views');
        view()->composer('*', 'ShvetsGroup\JetPages\ViewComposers\LocaleComposer');

        $this->app['router']->aliasMiddleware('static-cache', StaticCache::class);

        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}

if (!function_exists('content_path')) {
    /**
     * Get the content path.
     *
     * @param  string  $path
     * @return string
     */
    function content_path($path = '')
    {
        return base_path(config('jetpages.content_root', 'resources/content').($path ? DIRECTORY_SEPARATOR.$path : $path));
    }
}
