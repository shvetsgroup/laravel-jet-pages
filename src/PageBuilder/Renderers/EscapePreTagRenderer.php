<?php

namespace ShvetsGroup\JetPages\PageBuilder\Renderers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;

class EscapePreTagRenderer extends AbstractRenderer
{
    /**
     * @param $content
     * @param  Page  $page
     * @param  PageCollection  $pages
     * @return string
     */
    public function renderContent($content, Page $page, PageCollection $pages)
    {
        if ($content) {
            $content = preg_replace_callback('|<pre([^>]*?)class="([^"]* )?code( [^"]*)?"([^>]*?)>([\s\S]*?)</pre>|u', function ($matches) {
                return '<pre'.$matches[1].'class="'.$matches[2].'code'.$matches[3].'"'.$matches[4].'>'.htmlentities($matches[5]).'</pre>';
            }, $content);
        }
        return $content;
    }
}