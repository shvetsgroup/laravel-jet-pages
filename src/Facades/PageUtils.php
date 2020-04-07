<?php

namespace ShvetsGroup\JetPages\Facades;

use Illuminate\Support\Facades\Facade;

class PageUtils extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'page.utils';
    }
}