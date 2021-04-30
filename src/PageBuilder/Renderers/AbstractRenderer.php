<?php

namespace ShvetsGroup\JetPages\PageBuilder\Renderers;

use ShvetsGroup\JetPages\Facades\PageUtils;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;

/**
 * This class eases decoration of multiple content fields. You only need to define one function to decorate all content
 * fields.
 */
abstract class AbstractRenderer implements Renderer
{
    /**
     * @var PageUtils
     */
    protected $pageUtils;

    public function __construct()
    {
        $this->pageUtils = app('page.utils');
    }

    /**
     * @param  Page  $page
     * @param  PageCollection  $pages
     */
    public function render(Page $page, PageCollection $pages)
    {
        foreach ($this->getContentAttributes($page) as $attribute) {
            $content = $page->getAttribute($attribute);
            $content = $this->renderContent($content, $page, $pages);
            $page->setAttribute($attribute, $content);
        }
    }

    /**
     * Return "content" and all fields with start with "content_".
     * @param  Page  $page
     * @return array
     */
    public function getContentAttributes(Page $page)
    {
        return $page->getContentAttributes();
    }

    /**
     * Define in subclass to decorate a page field.
     *
     * @param $content
     * @param  Page  $page
     * @param  PageCollection  $pages
     * @return string
     */
    abstract public function renderContent($content, Page $page, PageCollection $pages);

    /**
     * @param $content
     * @param $code
     * @return string
     */
    public static function escapeCodeFragments($content, &$code, $pattern = null)
    {
        $content = preg_replace_callback($pattern ?? '#(?:```([\s\S]+?)```|<code([^>]*)>([\s\S]+?)</code>|<pre([^>]*)>([\s\S]+?)</pre>|<script([^>]*)>([\s\S]+?)</script>|<style([^>]*)>([\s\S]+?)</style>|%%%([\s\S]+?)%%%)#u',
            function ($m) use (&$code) {
                $code[] = $m[0];
                return "#%#%#".count($code)."#%#%#";
            }, $content);

        return $content;
    }

    /**
     * @param $content
     * @param $code
     * @return string
     */
    public static function unescapeCodeFragments($content, $code)
    {
        return preg_replace_callback('|#%#%#([0-9]+)#%#%#|u',
            function ($m) use ($code) {
                return $code[$m[1] - 1];
            }, $content);
    }

    public function start() {

    }

    public function finish() {

    }
}