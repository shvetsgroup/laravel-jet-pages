<?php namespace ShvetsGroup\JetPages\Builders\PostProcessors;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;
use ShvetsGroup\JetPages\Builders\StaticCache;

class StaticCachePostProcessor implements PostProcessor
{
    /**
     * @var StaticCache
     */
    private $staticCache;

    public function __construct()
    {
        $this->staticCache = app('jetpages.staticCache');
    }

    /**
     * @param Page[] $updatedPages
     * @param PageRegistry $registry
     */
    public function postProcess(array $updatedPages, PageRegistry $registry)
    {
        foreach ($updatedPages as $page) {
            $this->staticCache->cachePage($page);
        }
    }
}