<?php

namespace ShvetsGroup\JetPages\PageBuilder\PostProcessors;

use ShvetsGroup\JetPages\Page\PageCollection;

interface PostProcessor
{
    /**
     * @param  PageCollection  $updatedPages
     * @param  PageCollection  $pages
     */
    public function postProcess(PageCollection $updatedPages, PageCollection $pages);
}