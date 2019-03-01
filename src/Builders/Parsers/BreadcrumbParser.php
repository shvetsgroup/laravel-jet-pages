<?php namespace ShvetsGroup\JetPages\Builders\Parsers;

use Illuminate\Support\Arr;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

class BreadcrumbParser implements Parser
{
    /**
     * @param Page $page
     * @param PageRegistry $registry
     * @throws \RuntimeException
     */
    public function parse(Page $page, PageRegistry $registry)
    {
        $locale = $page->getAttribute('locale');

        $breadcrumbPaths = $page->getAttribute('breadcrumb');
        if (empty($breadcrumbPaths)) {
            $breadcrumbPaths = $this->getBreadcrumbPaths($page->getAttribute('slug'), $locale);
            if (count($breadcrumbPaths) == 1) {
                return;
            }
        }

        $breadcrumb = [];
        foreach ($breadcrumbPaths as $path) {
            if (is_array($path)) {
                $title = Arr::get($path, 'title');
                $slug = Arr::get($path, 'slug');
            }
            else {
                $slug = $path;
            }

            $p = $registry->findBySlug($locale, Page::uriToSlug($slug));
            if (!$p) {
                throw new \RuntimeException("Can not find page with id '$locale/$slug'.");
            }
            $breadcrumb[] = [
                'href' => $p->uri(true, true),
                'title' => $title ?? ($p->getAttribute('title_short') ?: $p->getAttribute('title')),
            ];
        }
        $page->setAttribute('breadcrumb', $breadcrumb);
    }

    protected function getBreadcrumbPaths($permalink, $locale)
    {
        $outline = app('jetpages.outline')->getFlatOutline(null, $locale);

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