<?php

namespace ShvetsGroup\JetPages\PageBuilder;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Filesystem\Filesystem;
use ShvetsGroup\JetPages\PageBuilder\PostProcessors\MenuPostProcessor;

class PageMenu
{
    /**
     * @var Store
     */
    private $cache;

    /**
     * @var Filesystem
     */
    protected $files;

    protected $menus = [];

    public function __construct()
    {
        $this->files = app('Illuminate\Filesystem\Filesystem');
        $this->cache = app('cache.store');
    }

    public function getMenu($locale = null)
    {
        $locale = $locale ?? app()->getLocale();

        if (!isset($this->menus[$locale])) {
            $this->menus[$locale] = $this->cache->get('jetpages:menu:'.$locale);
            if ($this->menus[$locale] === null) {
                $menuProcessor = new MenuPostProcessor();
                $this->menus[$locale] = $menuProcessor->rebuildCache($locale);
            }
        }

        return $this->menus[$locale];
    }
}