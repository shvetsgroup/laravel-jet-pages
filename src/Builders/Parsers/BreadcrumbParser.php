<?php namespace ShvetsGroup\JetPages\Builders\Parsers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

class BreadcrumbParser implements Parser
{
    /**
     * @param Page $page
     * @param PageRegistry $registry
     */
    public function parse(Page $page, PageRegistry $registry)
    {
        $locale = $page->getAttribute('locale');

        $breadcrumbPaths = $this->getBreadcrumbPaths($page->getAttribute('slug'));
        if (count($breadcrumbPaths) == 1) {
            return;
        }

        $breadcrumb = [];
        foreach ($breadcrumbPaths as $slug) {
            $p = $registry->findBySlug($locale, Page::uriToSlug($slug));
            $breadcrumb[] = [
                'href' => $p->uri(true, true),
                'title' => $p->getAttribute('title_short') ?: $p->getAttribute('title'),
            ];
        }
        $page->setAttribute('breadcrumb', $breadcrumb);
    }

    protected function getBreadcrumbPaths($permalink)
    {
        $outline = app('jetpages.outline')->getFlatOutline();

        $breadcrumb = ['/'];
        if (!isset($outline[$permalink])) {
            return $breadcrumb;
        }
        $prev = 0;
        foreach ($outline as $path => $depth) {
            if ($prev > $depth) {
                for ($i = $depth; $i < count($breadcrumb); $i++) {
                    array_pop($breadcrumb);
                }
            }
            $breadcrumb[$depth] = $path;
            if ($path == $permalink) {
                array_pop($breadcrumb);

                return $breadcrumb;
            }
            $prev = $depth;
        }
        return $breadcrumb;
    }
}