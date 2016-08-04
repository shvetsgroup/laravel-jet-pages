<?php namespace ShvetsGroup\JetPages\Builders\Renderers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

interface Renderer
{
    /**
     * @param Page $page
     * @param PageRegistry $registry
     * @return
     */
    public function render(Page $page, PageRegistry $registry);
}