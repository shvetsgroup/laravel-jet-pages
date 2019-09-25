<?php

namespace ShvetsGroup\JetPages\Builders\Parsers;

use RuntimeException;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

class NavigationParser implements Parser
{
    /**
     * @param  Page  $page
     * @param  PageRegistry  $registry
     */
    public function parse(Page $page, PageRegistry $registry)
    {
        $depths = app('jetpages.outline')->getFlatOutline(null, $page->getAttribute('locale'));
        $locale = $page->getAttribute('locale');
        $slug = $page->getAttribute('slug');
        if (!isset($depths[$slug])) {
            return;
        }

        $ordered_list = array_keys($depths);
        $ordered_list_indexes = array_flip($ordered_list);
        $index = $ordered_list_indexes[$slug];

        $getNavData = function ($locale, $slug) use ($registry) {
            $page = $registry->findBySlug($locale, $slug);
            if (!$page) {
                if (config('app.debug')) {
                    throw new RuntimeException("Can not find page with id '$locale/$slug'.");
                } else {
                    return null;
                }
            }
            return [
                'href' => $page->uri(true, true),
                'title' => $page->getAttribute('title_short') ?: $page->getAttribute('title'),
            ];
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