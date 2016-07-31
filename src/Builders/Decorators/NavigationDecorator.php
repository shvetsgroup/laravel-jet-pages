<?php namespace ShvetsGroup\JetPages\Builders\Decorators;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

class NavigationDecorator implements Decorator
{
    /**
     * @param Page $page
     * @param PageRegistry $registry
     */
    public function decorate(Page $page, PageRegistry $registry = null)
    {
        $depths = app('outline')->getFlatOutline();
        $slug = $page->getAttribute('slug');
        if (!isset($depths[$slug])) {
            return;
        }

        $ordered_list = array_keys($depths);
        $ordered_list_indexes = array_flip($ordered_list);
        $index = $ordered_list_indexes[$slug];

        $getNavData = function ($slug) use ($registry) {
            $nav_attributes = ['slug', 'title'];
            return $registry->getPageData($slug, $nav_attributes);
        };

        if (isset($ordered_list[$index - 1]) && $depths[$slug] > 1) {
            $prev_slug = $ordered_list[$index - 1];
            $page->setAttribute('prev', $getNavData($prev_slug));
        }
        if (isset($ordered_list[$index + 1]) && $depths[$ordered_list[$index + 1]] > 1) {
            $next_slug = $ordered_list[$index + 1];
            $page->setAttribute('next', $getNavData($next_slug));
        }

        if ($depths[$slug] == 1) {
            $page->setAttribute('parent', $getNavData($slug));
        } else {
            for ($i = $index - 1; $i >= 0; $i--) {
                if ($depths[$ordered_list[$i]] > $depths[$ordered_list[$index]]) {
                    break;
                }
                if ($depths[$ordered_list[$i]] < $depths[$ordered_list[$index]]) {
                    $page->setAttribute('parent', $getNavData($ordered_list[$i]));
                    break;
                }
            }
        }
    }
}