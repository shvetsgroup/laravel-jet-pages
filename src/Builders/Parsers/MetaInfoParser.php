<?php

namespace ShvetsGroup\JetPages\Builders\Parsers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;
use Symfony\Component\Yaml\Yaml;

class MetaInfoParser implements Parser
{
    /**
     * @param Page $page
     * @param PageRegistry $registry
     */
    public function parse(Page $page, PageRegistry $registry)
    {
        $content = $page->getAttribute('content');

        if (!$content) {
            return;
        }

        $matches = [];
        if (!preg_match_all('#(^|(?<=[\n\r]))\-\-\-\R#u', $content, $matches) || count($matches[0]) < 2) {
            return;
        }

        $values = preg_split('|^\-\-\-\R|mu', $content, 2, PREG_SPLIT_NO_EMPTY);

        if (count($values) == 1) {
            $page->setAttribute('content', $values[0]);
        } else {
            list($meta, $content) = $values;
            $page->setAttribute('content', $content);

            $meta = Yaml::parse($meta);
            foreach ($meta as $key => $value) {
                $page->setAttribute($key, $value);
            }
        }
    }
}