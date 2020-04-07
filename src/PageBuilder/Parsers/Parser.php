<?php

namespace ShvetsGroup\JetPages\PageBuilder\Parsers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;

interface Parser
{
    public function parse(Page $page, PageCollection $pages);
}