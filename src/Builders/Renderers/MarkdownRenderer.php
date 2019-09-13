<?php

namespace ShvetsGroup\JetPages\Builders\Renderers;

use Illuminate\Contracts\Cache\Store;
use League\CommonMark\Block\Renderer\DocumentRenderer;
use League\CommonMark\Converter;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use ShvetsGroup\JetPages\Builders\Renderers\MarkdownOverrides\CustomDocument;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

class MarkdownRenderer extends AbstractRenderer
{
    /**
     * @var Store
     */
    private $cache;

    protected $converters = [];
    protected $docParsers = [];
    protected $references = [];
    protected $referenceLocale = null;

    public function __construct()
    {
        $this->cache = app('cache.store');
    }

    /**
     * @param $content
     * @param  Page  $page
     * @param  PageRegistry  $registry
     * @return string
     */
    public function renderContent($content, Page $page, PageRegistry $registry)
    {
        if ($page->getAttribute('extension') == 'md') {
            $content = $this->mdToHtml($content, $page->locale, $registry);
        }

        return $content;
    }

    /**
     * Convert markdown into HTML.
     */
    public function mdToHtml($content, $locale, PageRegistry $registry)
    {
        if (!config('jetpages.cache_markdown')) {
            return $this->getConverter($locale, $registry)->convertToHtml($content);
        }

        $hash = $locale.md5($content);

        if (!$this->cache->has($hash)) {
            $result = $this->getConverter($locale, $registry)->convertToHtml($content);
            $this->cache->forever($hash, $result);
            return $result;
        }

        return $this->cache->get($hash);
    }

    /**
     * Get Markdown converter for a locale.
     */
    protected function getConverter($locale, PageRegistry $registry)
    {
        if (!isset($this->converters[$locale])) {
            $env = Environment::createCommonMarkEnvironment();
            $env->mergeConfig([
                'html_input' => 'allow',
            ]);
            $env->addBlockRenderer(CustomDocument::class, new DocumentRenderer());
            $renderer = new HtmlRenderer($env);
            $this->docParsers[$locale] = new MarkdownOverrides\CustomDocParser($env);
            $this->docParsers[$locale]->setReferences($this->getAllReferences($locale, $registry));
            $this->converters[$locale] = new Converter($this->docParsers[$locale], $renderer);
        }

        return $this->converters[$locale];
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