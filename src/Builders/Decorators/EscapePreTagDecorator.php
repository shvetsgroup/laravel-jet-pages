<?php namespace ShvetsGroup\JetPages\Builders\Decorators;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

class EscapePreTagDecorator implements Decorator
{
    /**
     * @param Page $page
     * @param PageRegistry $registry
     */
    public function decorate(Page $page, PageRegistry $registry = null)
    {
        $src = $page->getAttribute('src');
        $src = preg_replace_callback('|<pre([^>]*?)class="([^"]* )?code( [^"]*)?"([^>]*?)>([\s\S]*?)</pre>|', function ($matches) {
            return '<pre' . $matches[1] . 'class="' . $matches[2] . 'code' . $matches[3] . '"' . $matches[4] . '>' . htmlentities($matches[5]) . '</pre>';
        }, $src);
        $page->setAttribute('src', $src);
    }
}