<?php namespace ShvetsGroup\JetPages\Builders\Decorators\Content;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;
use function ShvetsGroup\JetPages\content_path;

class IncludeDecorator extends ContentDecorator
{
    /**
     * @param $content
     * @param Page $page
     * @param PageRegistry $registry
     * @return string
     */
    public function decorateContent($content, Page $page, PageRegistry $registry)
    {
        $files = app('Illuminate\Filesystem\Filesystem');
        $regexp = '|!INCLUDE\s+"([^"]*)"|';
        $content = preg_replace_callback($regexp, function($matches) use ($files) {
            $include_path = $matches[1];
            $contents = $files->get(content_path($include_path));
            return $contents;
        }, $content);

        return $content;
    }
}