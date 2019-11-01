<?php

// Add these routes after bootstrap is done in order to make them last in
// the route list. Otherwise, catch-all route will break some other
// routes registered after it.
app()->booted(function () {

    $router = app('router');

    $router->namespace('ShvetsGroup\JetPages\Controllers')->group(function () use ($router) {
        $router->get('ajax/jetpages/timestamp', 'PageController@getContentTimestamp');

        $router->group(['middleware' => 'static-cache'], function () use ($router) {
            $router->get('robots.txt', 'RobotsTxtController@robots');
            $router->get('sitemap.xml', 'SiteMapController@sitemap');
        });

        // Specific override for a front page to overcome default laravel's route in app/Http/routes.php
        $router->middleware('static-cache')->get('/', 'PageController@show');

        $exceptions = [];

        if ($nova = config('nova.path', '')) {
            $nova = trim($nova, '/');
            $exceptions[] = $nova.'$';
            $exceptions[] = $nova.'/';
            $exceptions[] = 'nova-api/';
        }

        $exceptions = $exceptions ? '(?!'.implode('|', $exceptions).')' : '';

        $router->middleware('static-cache')->get('{all}', 'PageController@show')->where(['all' => '^'.$exceptions.'.*$']);

    });

});

