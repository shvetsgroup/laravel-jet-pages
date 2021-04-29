<?php

use ShvetsGroup\JetPages\Controllers\PageController;
use ShvetsGroup\JetPages\Controllers\RobotsTxtController;
use ShvetsGroup\JetPages\Controllers\SiteMapController;
use ShvetsGroup\JetPages\Middleware\StaticCache;

// Add these routes after bootstrap is done in order to make them last in
// the route list. Otherwise, catch-all route will break some other
// routes registered after it.
app()->booted(function () {

    /**
     * @var $router \Illuminate\Routing\Router
     */
    $router = app('router');

    $router->group(['middleware' => StaticCache::class], function () use ($router) {
        $router->get('robots.txt', [RobotsTxtController::class, 'robots']);
        $router->get('sitemap.xml', [SiteMapController::class, 'sitemap']);
    });

    // Specific override for a front page to overcome default laravel's route in app/Http/routes.php
    $router->middleware([StaticCache::class])->get('/', [PageController::class, 'show']);

    $exceptions = [];

    if ($nova = config('nova.path', '')) {
        $nova = trim($nova, '/');
        $exceptions[] = $nova.'$';
        $exceptions[] = $nova.'/';
        $exceptions[] = 'nova-api/';
    }

    $exceptions = $exceptions ? '(?!'.implode('|', $exceptions).')' : '';

    $router->middleware([StaticCache::class])
        ->get('{all}', [PageController::class, 'show',])
        ->where(['all' => '^'.$exceptions.'.*$']);
});
