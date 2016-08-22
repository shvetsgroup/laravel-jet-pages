<?php

Route::group(['middleware' => ['static-cache']], function () {
    Route::get('robots.txt', 'RobotsTxtController@robots');
    Route::get('sitemap.xml', 'SiteMapController@sitemap');
    Route::get('ajax/jetpages/timestamp.json', 'PageController@getContentTimestamp');

    // Specific override for a front page to overcome default laravel's route in app/Http/routes.php
    Route::get('/', 'PageController@show');
    Route::get('{all}', 'PageController@show')->where(['all' => '.*']);
});