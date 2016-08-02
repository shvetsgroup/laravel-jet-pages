<?php namespace ShvetsGroup\JetPages\Builders\Decorators\Content;

use ShvetsGroup\JetPages\Builders\Decorators\Decorator;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

/**
 * This class eases decoration of multiple content fields. You only need to define one function to decorate all content
 * fields.
 */
abstract class ContentDecorator implements Decorator
{
    /**
     * @param Page $page
     * @param PageRegistry $registry
     */
    public function decorate(Page $page, PageRegistry $registry)
    {
        foreach ($this->getContentFields($page) as $field) {
            $content = $page->getAttribute($field);
            $content = $this->decorateContent($content, $page, $registry);
            $page->setAttribute($field, $content);
        }
    }

    /**
     * Return "content" and all fields with start with "content_".
     * @return array
     */
    public function getContentFields(Page $page) {
        return array_filter(array_keys($page->toArray()), function($key){ return preg_match('#content($|_)#', $key); });
    }

    /**
     * Define in subclass to decorate a page field.
     *
     * @param $content
     * @param Page $page
     * @param PageRegistry $registry
     * @return string
     */
    abstract public function decorateContent($content, Page $page, PageRegistry $registry);
}