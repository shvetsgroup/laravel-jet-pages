<?php

namespace ShvetsGroup\JetPages\Builders\PostProcessors;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;
use ShvetsGroup\JetPages\Builders\StaticCache;

class StaticCachePostProcessor implements PostProcessor
{
    /**
     * @var StaticCache
     */
    private $staticCache;

    private $localization;

    public function __construct()
    {
        $this->staticCache = app('jetpages.staticCache');
        if (app()->bound('laravellocalization')) {
            $this->localization = app('laravellocalization');
        }
        else {
            $this->localization = app();
        }
    }

    /**
     * @param Page[] $updatedPages
     * @param PageRegistry $registry
     */
    public function postProcess(array $updatedPages, PageRegistry $registry)
    {
        $current_locale = config('app.locale');
        foreach ($updatedPages as $page) {
            $this->localization->setLocale($page->getAttribute('locale'));
            $this->staticCache->cachePage($page);
        }
        $this->localization->setLocale($current_locale);
    }
}