<?php

namespace ShvetsGroup\JetPages\PageBuilder\Renderers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;

interface Renderer
{
    /**
     * @param  Page  $page
     * @param  PageCollection  $pages
     */
    public function render(Page $page, PageCollection $pages);
}