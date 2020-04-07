<?php

namespace ShvetsGroup\JetPages\PageBuilder\PostProcessors;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;
use ShvetsGroup\JetPages\PageBuilder\PageCache;

class StaticCachePostProcessor implements PostProcessor
{
    /**
     * @var PageCache
     */
    private $pageCache;

    private $localization;

    public function __construct()
    {
        $this->pageCache = new PageCache();

        if (app()->bound('laravellocalization')) {
            $this->localization = app('laravellocalization');
        } else {
            $this->localization = app();
        }
    }

    /**
     * @param  PageCollection  $updatedPages
     * @param  PageCollection  $pages
     */
    public function postProcess(PageCollection $updatedPages, PageCollection $pages)
    {
        $current_locale = app()->getLocale();

        $updatedPages->each(function (Page $page) {
            $this->localization->setLocale($page->getAttribute('locale'));
            $this->pageCache->cachePage($page);
        });

        $this->localization->setLocale($current_locale);
    }
}