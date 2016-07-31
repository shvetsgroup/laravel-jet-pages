<?php namespace ShvetsGroup\JetPages\Builders\Decorators;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;
use function ShvetsGroup\JetPages\content_path;

class IncludeDecorator implements Decorator
{
    /**
     * @param Page $page
     * @param PageRegistry $registry
     */
    public function decorate(Page $page, PageRegistry $registry = null)
    {
        $files = app('Illuminate\Filesystem\Filesystem');
        $src = $page->getAttribute('src');
        $regexp = '|!INCLUDE\s+"([^"]*)"|';
        $src = preg_replace_callback($regexp, function($matches) use ($files) {
            $include_path = $matches[1];
            $contents = $files->get(content_path($include_path));
            return $contents;
        }, $src);
        $page->setAttribute('src', $src);
    }
}