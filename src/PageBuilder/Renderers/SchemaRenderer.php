<?php

namespace ShvetsGroup\JetPages\PageBuilder\Renderers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;

/**
 * Adds Schema.org definitions to all pages.
 */
class SchemaRenderer implements Renderer
{
    /**
     * @param  Page  $page
     * @param  PageCollection  $pages
     */
    public function render(Page $page, PageCollection $pages)
    {
        $locale = $page->getAttribute('locale');
        $schema = config('jetpages.schema');

        if (!isset($schema[$locale])) {
            return;
        }

        $pageSchema = $page->getAttribute('schema');

        if (!is_array($pageSchema)) {
            $pageSchema = [];
        }

        $page->setAttribute('schema', array_merge($schema[$locale], $pageSchema));
    }
}