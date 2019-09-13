<?php

namespace ShvetsGroup\JetPages\Builders\Renderers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

/**
 * Adds Schema.org definitions to all pages.
 */
class SchemaRenderer implements Renderer
{
    /**
     * @param  Page  $page
     * @param  PageRegistry  $registry
     */
    public function render(Page $page, PageRegistry $registry)
    {
        $locale = $page->locale;
        $schema = config('jetpages.schema');

        if (!isset($schema[$locale])) {
            return;
        }

        if (!is_array($page->schema)) {
            $page->schema = [];
        }

        $page->schema = array_merge($schema[$locale], $page->schema);
    }
}