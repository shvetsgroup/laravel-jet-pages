<?php

namespace ShvetsGroup\JetPages\Builders\PostProcessors;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

interface PostProcessor
{
    /**
     * @param  Page[]  $updatedPages
     * @param  PageRegistry  $registry
     * @return
     */
    public function postProcess(array $updatedPages, PageRegistry $registry);
}