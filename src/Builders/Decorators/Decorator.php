<?php namespace ShvetsGroup\JetPages\Builders\Decorators;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

interface Decorator
{
    /**
     * @param Page $page
     * @param PageRegistry $registry
     * @return
     */
    public function decorate(Page $page, PageRegistry $registry = null);
}