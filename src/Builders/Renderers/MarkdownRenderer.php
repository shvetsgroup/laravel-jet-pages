<?php namespace ShvetsGroup\JetPages\Builders\Renderers;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Converter;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

class MarkdownRenderer extends AbstractRenderer
{
    protected $converter;

    protected $docParser;

    protected $references = [];
    protected $referenceLocale = null;

    public function __construct()
    {
        $env = Environment::createCommonMarkEnvironment();
        $env->mergeConfig([
            'html_input' => 'allow',
        ]);
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
        if ($page->getAttribute('extension') == 'md') {

            // This speeds up references rendering for about 800%.
            if (! isset($this->references[$page->locale])) {
                $this->references[$page->locale] = $this->getAllReferences($page->locale, $registry);
            }
            if ($this->referenceLocale != $page->locale) {
                $this->docParser->setReferences($this->references[$page->locale]);
                $this->referenceLocale = $page->locale;
            }

            $content = $this->converter->convertToHtml($content);
        }

        return $content;
    }

    private function getAllReferences($page_locale, PageRegistry $registry)
    {
        $allPages = $registry->getAll();

        $default_locale = config('app.default_locale', '');

        $index = [
            $default_locale => [],
            $page_locale => [],
        ];

        foreach ($allPages as $aPage) {
            if ($aPage->locale == $default_locale || $aPage->locale == $page_locale) {
                $locale = $aPage->getAttribute('locale');
                $title = $aPage->getAttribute('title');

                $localizedPage = $aPage;
                if ($page_locale != $default_locale && $aPage->locale == $default_locale) {
                    $localizedPage = $registry->findBySlug($page_locale, $aPage->slug) ?: $localizedPage;
                }

                $localizedURL = $localizedPage->uri(true, true);
                $localizedTitle = $localizedPage->getAttribute('title');

                $index[$locale][$title] = [
                    'url' => $localizedURL,
                    'title' => $localizedTitle
                ];
            }
        }

        $result = [];
        foreach ($index as $locale => $allPages) {
            $result = array_merge($result, $allPages);
        }

        $references[$page_locale] = $result;

        return $references[$page_locale];
    }
}