<?php namespace ShvetsGroup\JetPages\Builders\Decorators\Content;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

class EscapePreTagDecorator extends ContentDecorator
{
    /**
     * @param $content
     * @param Page $page
     * @param PageRegistry $registry
     * @return string
     */
    public function decorateContent($content, Page $page, PageRegistry $registry)
    {
        if ($content) {
            $content = preg_replace_callback('|<pre([^>]*?)class="([^"]* )?code( [^"]*)?"([^>]*?)>([\s\S]*?)</pre>|', function ($matches) {
                return '<pre' . $matches[1] . 'class="' . $matches[2] . 'code' . $matches[3] . '"' . $matches[4] . '>' . htmlentities($matches[5]) . '</pre>';
            }, $content);
        }
        return $content;
    }
}