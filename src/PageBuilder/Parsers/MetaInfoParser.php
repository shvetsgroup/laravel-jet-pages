<?php

namespace ShvetsGroup\JetPages\PageBuilder\Parsers;

use Illuminate\Support\Arr;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;
use Symfony\Component\Yaml\Yaml;

class MetaInfoParser implements Parser
{
    /**
     * @param  Page  $page
     */
    public function parse(Page $page, PageCollection $pages)
    {
        $content = $page->getAttribute('content');

        if (!$content) {
            return;
        }

        $matches = [];
        if (!preg_match_all('#(^|(?<=[\n\r]))---\R#u', $content . "\n", $matches) || count($matches[0]) < 2) {
            return;
        }

        $values = preg_split('|^---\R|mu', $content . "\n", 3);
        if (isset($values[0]) && $values[0] === "") {
            array_shift($values);
        }

        if (count($values) == 1) {
            $page->setAttribute('content', trim(Arr::first($values), " \n"));
        } else {
            list($meta, $content) = $values;
            $page->setAttribute('content', trim($content, " \n"));

            $meta = Yaml::parse($meta);
            foreach ($meta as $key => $value) {
                $page->setAttribute($key, $value);
            }
        }
    }
}