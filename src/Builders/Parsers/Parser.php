<?php

namespace ShvetsGroup\JetPages\Builders\Parsers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

interface Parser
{
    /**
     * @param Page $page
     * @param PageRegistry $registry
     */
    public function parse(Page $page, PageRegistry $registry);
}