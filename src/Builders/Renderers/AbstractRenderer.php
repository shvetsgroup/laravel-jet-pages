<?php

namespace ShvetsGroup\JetPages\Builders\Renderers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

/**
 * This class eases decoration of multiple content fields. You only need to define one function to decorate all content
 * fields.
 */
abstract class AbstractRenderer implements Renderer
{
    /**
     * @param  Page  $page
     * @param  PageRegistry  $registry
     */
    public function render(Page $page, PageRegistry $registry)
    {
        foreach ($this->getContentFields($page) as $field) {
            $content = $page->getAttribute($field);
            $content = $this->renderContent($content, $page, $registry);
            $page->setAttribute($field, $content);
        }
    }

    /**
     * Return "content" and all fields with start with "content_".
     * @param  Page  $page
     * @return array
     */
    public function getContentFields(Page $page)
    {
        return array_filter(array_keys($page->toArray()), function ($key) { return preg_match('#content($|_)#', $key); });
    }

    /**
     * Define in subclass to decorate a page field.
     *
     * @param $content
     * @param  Page  $page
     * @param  PageRegistry  $registry
     * @return string
     */
    abstract public function renderContent($content, Page $page, PageRegistry $registry);

    /**
     * @param $content
     * @param $code
     * @return string
     */
    public static function escapeCodeFragments($content, &$code)
    {
        $content = preg_replace_callback('#(```([\s\S]+?)```|<code([^>]*)>([\s\S]+?)</code>|<pre([^>]*)>([\s\S]+?)</pre>|<script([^>]*)>([\s\S]+?)</script>|<style([^>]*)>([\s\S]+?)</style>|%%%([\s\S]+?)%%%)#u',
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
}