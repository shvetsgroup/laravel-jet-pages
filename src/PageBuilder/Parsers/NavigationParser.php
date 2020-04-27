<?php

namespace ShvetsGroup\JetPages\PageBuilder\Parsers;

use RuntimeException;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;
use ShvetsGroup\JetPages\PageBuilder\PageOutline;

class NavigationParser implements Parser
{
    /**
     * @var PageOutline
     */
    protected $outline;

    /**
     * @var bool
     */
    protected $isDebug;

    public function __construct($outline = null)
    {
        $this->outline = $outline ?? (new PageOutline())->setFilename('outline');
        $this->isDebug = config('app.debug');
    }

    /**
     * @param  Page  $page
     * @param  PageCollection  $pages
     */
    public function parse(Page $page, PageCollection $pages)
    {
        $locale = $page->getAttribute('locale');
        $slug = $page->getAttribute('slug');

        $depths = $this->outline->getFlatOutline($locale);

        if (!isset($depths[$slug])) {
            return;
        }

        $ordered_list = array_keys($depths);
        $ordered_list_indexes = array_flip($ordered_list);
        $index = $ordered_list_indexes[$slug];

        $getNavData = function ($locale, $slug) use ($pages) {
            $page = $pages->findBySlug($locale, $slug);

            if (!$page) {
                if ($this->isDebug) {
                    throw new RuntimeException("Can not find page with id '$locale/$slug'.");
                } else {
                    return null;
                }
            }

            return $page->getTitleHrefArray();
        };

        if (isset($ordered_list[$index - 1]) && $depths[$slug] > 1) {
            $prev_slug = $ordered_list[$index - 1];
            $page->setAttribute('prev', $getNavData($locale, $prev_slug));
        }
        if (isset($ordered_list[$index + 1]) && $depths[$ordered_list[$index + 1]] > 1) {
            $next_slug = $ordered_list[$index + 1];
            $page->setAttribute('next', $getNavData($locale, $next_slug));
        }

        if ($depths[$slug] == 1) {
            $page->setAttribute('parent', $getNavData($locale, $slug));
        } else {
            for ($i = $index - 1; $i >= 0; $i--) {
                if ($depths[$ordered_list[$i]] > $depths[$ordered_list[$index]]) {
                    break;
                }
                if ($depths[$ordered_list[$i]] < $depths[$ordered_list[$index]]) {
                    $page->setAttribute('parent', $getNavData($locale, $ordered_list[$i]));
                    break;
                }
            }
        }
    }
}