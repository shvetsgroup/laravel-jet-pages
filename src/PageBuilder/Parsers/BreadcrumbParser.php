<?php

namespace ShvetsGroup\JetPages\PageBuilder\Parsers;

use Illuminate\Support\Arr;
use ShvetsGroup\JetPages\Facades\PageUtils;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;
use ShvetsGroup\JetPages\PageBuilder\PageOutline;

class BreadcrumbParser implements Parser
{
    /**
     * @var PageOutline
     */
    protected $outline;

    /**
     * @var bool
     */
    protected $startBreadcrumbFromIndexPage;

    /**
     * @var bool
     */
    protected $minBreadcrumbCount;

    /**
     * @var PageUtils
     */
    protected $pageUtils;

    public function __construct($outline = null)
    {
        $this->outline = $outline ?: app('page.outline');
        $this->startBreadcrumbFromIndexPage = config('jetpages.start_breadcrumb_from_index_page', true);
        $this->minBreadcrumbCount = config('jetpages.min_breadcrumb_count', 2);
        $this->pageUtils = app('page.utils');
    }

    /**
     * @param  Page  $page
     * @param  PageCollection  $pages
     */
    public function parse(Page $page, PageCollection $pages)
    {
        $locale = $page->getAttribute('locale');

        $breadcrumbParts = $page->getAttribute('breadcrumb');

        if (empty($breadcrumbParts)) {
            $breadcrumbParts = $this->parseBreadcrumbFromOutline($page);
        }

        if (count($breadcrumbParts) < $this->minBreadcrumbCount) {
            return;
        }

        $breadcrumb = [];
        foreach ($breadcrumbParts as $part) {
            if (is_array($part)) {
                $slug = Arr::get($part, 'slug');
                $customTitle = Arr::get($part, 'title');
            } else {
                $slug = $part;
            }

            $parentPage = $pages->findBySlug($locale, $this->pageUtils->uriToSlug($slug));

            if (!$parentPage) {
                throw new PageParsingException("Can not find page with id '$locale/$slug'.");
            }

            $breadcrumb[] = $parentPage->getTitleHrefArray($customTitle ?? null);
        }

        $page->setAttribute('breadcrumb', $breadcrumb);
    }

    protected function parseBreadcrumbFromOutline(Page $page)
    {
        $locale = $page->getAttribute('locale');
        $slug = $page->getAttribute('slug');

        $outline = $this->outline->getFlatOutline($locale);

        $breadcrumb = [];

        if ($this->startBreadcrumbFromIndexPage) {
            $breadcrumb[] = '/';
        }

        if (!isset($outline[$slug])) {
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
            if ($path == $slug) {
                array_pop($breadcrumb);

                return $breadcrumb;
            }
            $prev = $depth;
        }

        return $breadcrumb;
    }
}