<?php namespace ShvetsGroup\JetPages\Builders\Renderers;

use League\CommonMark\Converter;
use League\CommonMark\HtmlRenderer;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

class MarkdownRenderer extends AbstractRenderer
{
    protected $converter;
    protected $docParser;

    public function __construct()
    {
        $env = app('markdown.environment');
        $renderer = new HtmlRenderer($env);
        $this->docParser = new MarkdownOverrides\CustomDocParser($env);
        $this->converter = new Converter($this->docParser, $renderer);
    }

    /**
     * @param $content
     * @param Page $page
     * @param PageRegistry $registry
     * @return string
     */
    public function renderContent($content, Page $page, PageRegistry $registry)
    {
        // This speeds up references rendering for about 800%.
        static $referencesAdded = false;
        if (!$referencesAdded) {
            $this->docParser->addReferences($this->getAllReferences($page, $registry));
            $referencesAdded = true;
        }

        if ($page->getAttribute('extension') == 'md') {
            $content = $this->converter->convertToHtml($content);
        }
        return $content;
    }

    private function getAllReferences(Page $page, PageRegistry $registry) {
        $patterns = $registry->getAll();
        $index = [];
        foreach ($patterns as $pattern) {
            $title = $pattern->title_en ?: $pattern->title;
            if ($title) {
                $index[$title] = url(Page::makeLocaleUri($page->locale, $pattern->slug));
            }
        }
        return $index;
    }
}