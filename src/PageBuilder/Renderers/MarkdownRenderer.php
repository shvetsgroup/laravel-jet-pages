<?php

namespace ShvetsGroup\JetPages\PageBuilder\Renderers;

use Illuminate\Contracts\Cache\Store;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Event\DocumentPreParsedEvent;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;
use ShvetsGroup\JetPages\PageBuilder\Renderers\MarkdownOverrides\ReferenceCacheProcessor;

class MarkdownRenderer extends AbstractRenderer
{
    /**
     * @var Store
     */
    protected $cache;

    protected $converters = [];
    protected $references = [];

    public function __construct()
    {
        parent::__construct();
        $this->cache = app('cache.store');
    }

    /**
     * @param $content
     * @param  Page  $page
     * @param  PageCollection  $pages
     * @return string
     */
    public function renderContent($content, Page $page, PageCollection $pages)
    {
        if ($page->getAttribute('extension') == 'md') {
            $content = $this->mdToHtml($content, $page->getAttribute('locale'), $pages);
        }

        return $content;
    }

    /**
     * Convert markdown into HTML.
     */
    public function mdToHtml($content, $locale, PageCollection $pages)
    {
        if (!config('jetpages.cache_markdown')) {
            return $this->getConverter($locale, $pages)->convertToHtml($content);
        }

        $hash = 'md_'.$locale.md5($content);

        if (!$this->cache->has($hash)) {
            $result = $this->getConverter($locale, $pages)->convertToHtml($content);
            $this->cache->forever($hash, $result);
            return $result;
        }

        return $this->cache->get($hash);
    }

    /**
     * Get Markdown converter for a locale.
     */
    protected function getConverter($locale, PageCollection $pages)
    {
        if (!isset($this->converters[$locale])) {
            $env = Environment::createCommonMarkEnvironment();
            $env->mergeConfig([
                'html_input' => 'allow',
            ]);
            $referenceCacheProcessor = new ReferenceCacheProcessor($env);
            $referenceCacheProcessor->setReferences($this->getAllReferences($locale, $pages));
            $env->addEventListener(DocumentPreParsedEvent::class, [$referenceCacheProcessor, 'onDocumentPreParsed']);
            $this->converters[$locale] = new CommonMarkConverter([], $env);
        }

        return $this->converters[$locale];
    }

    private function getAllReferences($pageLocale, PageCollection $pages)
    {
        $defaultLocale = config('app.default_locale', '');

        $index = [
            $defaultLocale => [],
            $pageLocale => [],
        ];

        $pages->filter(function ($page) use ($pageLocale, $defaultLocale) {
            return in_array($page->locale, [$pageLocale, $defaultLocale]);
        })->each(function ($page) use ($pages, $pageLocale, $defaultLocale, &$index) {
            $locale = $page->getAttribute('locale');
            $title = $page->getAttribute('title');

            $localizedPage = $page;
            if ($pageLocale != $defaultLocale && $locale == $defaultLocale) {
                $localizedPage = $pages->findBySlug($pageLocale, $page->slug) ?: $localizedPage;
            }

            $localizedURL = $localizedPage->getAttribute('href') ?? '';
            $localizedTitle = $localizedPage->getAttribute('title') ?? '';

            $index[$locale][$title] = [
                'url' => $localizedURL,
                'title' => $localizedTitle,
            ];
        });

        $result = [];
        foreach ($index as $locale => $pages) {
            $result = array_merge($result, $pages);
        }

        $references[$pageLocale] = $result;

        return $references[$pageLocale];
    }
}
