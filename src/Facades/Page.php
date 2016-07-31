<?php namespace ShvetsGroup\JetPages\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Illuminate\Events\Dispatcher
 */
class Page extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Page::class;
    }
}
