<?php namespace ShvetsGroup\JetPages\Builders\Decorators\Content;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;
use League\CommonMark\Converter;

class MarkdownDecorator extends ContentDecorator
{
    protected $converter;

    public function __construct(Converter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * @param $content
     * @param Page $page
     * @param PageRegistry $registry
     * @return string
     */
    public function decorateContent($content, Page $page, PageRegistry $registry)
    {
        if ($page->getAttribute('extension') == 'md') {
            $content = $this->converter->convertToHtml($content);
        }
        return $content;
    }
}