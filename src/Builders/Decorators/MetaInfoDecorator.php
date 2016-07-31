<?php namespace ShvetsGroup\JetPages\Builders\Decorators;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;
use Symfony\Component\Yaml\Yaml;

class MetaInfoDecorator implements Decorator
{
    /**
     * @param Page $page
     * @param PageRegistry $registry
     */
    public function decorate(Page $page, PageRegistry $registry = null)
    {
        $src = $page->getAttribute('src');

        if (!$src) {
            return;
        }

        $matches = [];
        if (!preg_match_all('#(^|(?<=[\n\r]))\-\-\-\R#', $src, $matches) || count($matches[0]) < 2) {
            return;
        }

        $values = preg_split('|^\-\-\-\R|m', $src, 2, PREG_SPLIT_NO_EMPTY);

        if (count($values) == 1) {
            $page->setAttribute('src', $values[0]);
        } else {
            list($meta, $src) = $values;
            $page->setAttribute('src', $src);

            $meta = Yaml::parse($meta);
            foreach ($meta as $key => $value) {
                $page->setAttribute($key, $value);
            }
        }
    }
}