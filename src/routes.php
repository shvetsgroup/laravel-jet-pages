<?php

Route::group(['middleware' => []], function () {
    Route::get('ajax/jetpages/timestamp', 'PageController@getContentTimestamp');

    // Specific override for a front page to overcome default laravel's route in app/Http/routes.php
    Route::get('/', 'PageController@show');
    Route::get('{all}', 'PageController@show')->where(['all' => '.*']);
});