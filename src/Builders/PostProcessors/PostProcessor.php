<?php namespace ShvetsGroup\JetPages\Builders\PostProcessors;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

interface PostProcessor
{
    /**
     * @param Page $page
     * @param PageRegistry $registry
     * @return
     */
    public function postProcess(Page $page, PageRegistry $registry);
}