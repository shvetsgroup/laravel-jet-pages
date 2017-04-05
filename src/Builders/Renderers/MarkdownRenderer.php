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
        $default_locale = config('app.default_locale', '');
        $page_locale = $page->locale;
        $index = [$page_locale => [], $default_locale => []];
        foreach ($patterns as $pattern) {
            if (!in_array($pattern->locale, [$page_locale, $default_locale])) {
                continue;
            }
            $title = $pattern->title_en ?: $pattern->title;
            if ($title) {
                if (!isset($index[$pattern->locale])) {
                    $index[$pattern->locale] = [];
                }
                $index[$pattern->locale][$title] = url(Page::makeLocaleUri($page->locale, $pattern->slug));
            }
        }
        $result = [];
        foreach ($index as $locale => $pages) {
            $result = array_merge($result, $pages);
        }
        return $result;
    }
}