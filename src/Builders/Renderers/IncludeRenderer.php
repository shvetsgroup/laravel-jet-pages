<?php namespace ShvetsGroup\JetPages\Builders\Renderers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;
use function ShvetsGroup\JetPages\content_path;

class IncludeRenderer extends AbstractRenderer
{
    /**
     * @param $content
     * @param Page $page
     * @param PageRegistry $registry
     * @return string
     */
    public function renderContent($content, Page $page, PageRegistry $registry)
    {
        $files = app('Illuminate\Filesystem\Filesystem');
        $regexp = '|!INCLUDE\s+"([^"]*)"|';
        $content = preg_replace_callback($regexp, function($matches) use ($files) {
            $include_path = $matches[1];

            $full_path = content_path($include_path);
            $contents = $files->get($full_path);

            if (preg_match('/\.(png|gif|jpeg)$/', $include_path)) {
                $contents = '<img src="data:' . mime_content_type($full_path) . ';base64,' . base64_encode($contents) . '">';
            }

            return $contents;
        }, $content);

        return $content;
    }
}