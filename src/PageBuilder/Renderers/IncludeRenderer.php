<?php

namespace ShvetsGroup\JetPages\PageBuilder\Renderers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;
use function ShvetsGroup\JetPages\content_path;

class IncludeRenderer extends AbstractRenderer
{
    /**
     * @param $content
     * @param  Page  $page
     * @param  PageCollection  $pages
     * @return string
     */
    public function renderContent($content, Page $page, PageCollection $pages)
    {
        $files = app('Illuminate\Filesystem\Filesystem');
        $regexp = '|!INCLUDE\s+"([^"]*)"|u';
        $content = preg_replace_callback($regexp, function ($matches) use ($files) {
            $include_path = $matches[1];

            $full_path = content_path($include_path);

            if (!file_exists($full_path)) {
                $full_path = base_path($include_path);
            }

            if (preg_match('/\.(png|gif|jpe?g)$/u', $include_path)) {
                if (preg_match('/^img:/u', $include_path)) {
                    $full_path = preg_replace('/.*?img:/u', '', $full_path);
                    $contents = '<img src="'.$full_path.'" alt="" />';
                } else {
                    $contents = '<img src="data:'.mime_content_type($full_path).';base64,'.base64_encode($files->get($full_path)).'" alt="" />';
                }
            } else {
                $contents = $files->get($full_path);
                // Remove BOM, if any.
                $contents = preg_replace('/^\x{FEFF}/u', '', $contents);
                // Normalize line-endings.
                $contents = preg_replace("%(\r\n|\r|\n)%u", "\n", $contents);
                $contents = rtrim($contents, "\n");

                if (preg_match('/\.(html|md)$/u', $include_path)) {
                    $contents = preg_replace('|^\-\-\-\n([\s\S]*?)\-\-\-\n|u', '', $contents);
                }
            }

            return $contents;
        }, $content);

        return $content;
    }
}
