<?php

namespace ShvetsGroup\JetPages\PageBuilder\Renderers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;

/**
 * Adds Schema.org definitions to all pages.
 */
class SchemaRenderer implements Renderer
{
    public $schema;

    public function __construct()
    {
        $this->schema = config('jetpages.schema');
    }

    /**
     * @param  Page  $page
     * @param  PageCollection  $pages
     */
    public function render(Page $page, PageCollection $pages)
    {
        $locale = $page->getAttribute('locale');

        if (!isset($this->schema[$locale])) {
            return;
        }

        $pageSchema = $page->getAttribute('schema');

        if (!is_array($pageSchema)) {
            $pageSchema = [];
        }

        $page->setAttribute('schema', array_merge($this->schema[$locale], $pageSchema));
    }
}