<?php namespace ShvetsGroup\JetPages\Builders\Renderers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

class MarkdownRenderer extends AbstractRenderer
{
    protected $converter;

    public function __construct()
    {
        $this->converter = app('\League\CommonMark\Converter');
    }

    /**
     * @param $content
     * @param Page $page
     * @param PageRegistry $registry
     * @return string
     */
    public function renderContent($content, Page $page, PageRegistry $registry)
    {
        if ($page->getAttribute('extension') == 'md') {
            $content = $this->addReferences($content, $page, $registry);
            $content = $this->converter->convertToHtml($content);
        }
        return $content;
    }

    private function addReferences($content, Page $page, PageRegistry $registry) {
        static $reference = '';
        if (!$reference) {
            $patterns = $registry->getAll();
            $index = [];
            foreach ($patterns as $pattern) {
                $title = $pattern->title_en ?: $pattern->title;
                if ($title) {
                    $index[$title] = Page::makeLocaleUri($page->locale, $pattern->slug);
                }
            }
            ksort($index);
            $reference = "\n\n";
            foreach ($index as $title => $uri) {
                $reference .= "[$title]: /$uri\n";
            }
        }

        $content .= $reference;
        return $content;
    }
}