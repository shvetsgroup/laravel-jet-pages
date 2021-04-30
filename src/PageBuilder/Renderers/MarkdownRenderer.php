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
    protected $converters = [];
    protected $references = [];
    protected $isMarkdownCacheEnabled = false;

    static $cache = [];

    public function __construct()
    {
        parent::__construct();
        $this->isMarkdownCacheEnabled = config('jetpages.cache_markdown', false);
        if ($this->isMarkdownCacheEnabled) {
            if (! file_exists(storage_path('app/jetpages-md-cache'))) {
                mkdir(storage_path('app/jetpages-md-cache'), 0777, true);
            }
            if (! file_exists(storage_path('app/jetpages-md-cache/cache.txt'))) {
                file_put_contents(storage_path('app/jetpages-md-cache/cache.txt'), serialize([]));
            }
            static::$cache = unserialize(file_get_contents(storage_path('app/jetpages-md-cache/cache.txt')));
        }
    }

    protected function cachePath($content)
    {
        return md5($content);
    }

    protected function cacheHas($path)
    {
        return isset(static::$cache[$path]);
    }

    protected function cacheGet($path)
    {
        return static::$cache[$path];
    }

    protected function cacheSet($path, $content)
    {
        static::$cache[$path] = $content;
    }

    public function finish() {
        file_put_contents(storage_path('app/jetpages-md-cache/cache.txt'), serialize(static::$cache));
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
            if ($this->isMarkdownCacheEnabled) {
                $path = $this->cachePath($content);
                if ($this->cacheHas($path)) {
                    $content = $this->cacheGet($path);
                    return $content;
                }
            }

            $content = $this->mdToHtml($content, $page->getAttribute('locale'), $pages);

            if ($this->isMarkdownCacheEnabled) {
                $this->cacheSet($path, $content);
            }
        }

        return $content;
    }

    /**
     * Convert markdown into HTML.
     */
    public function mdToHtml($content, $locale, PageCollection $pages)
    {
        if (! config('jetpages.cache_markdown')) {
            return $this->getConverter($locale, $pages)->convertToHtml($content);
        }

        $path = $this->cachePath('md_'.$locale.$content);

        if (! $this->cacheHas($path)) {
            $result = $this->getConverter($locale, $pages)->convertToHtml($content);
            $this->cacheSet($path, $result);
            return $result;
        }

        return $this->cacheGet($path);
    }

    /**
     * Get Markdown converter for a locale.
     */
    protected function getConverter($locale, PageCollection $pages)
    {
        if (! isset($this->converters[$locale])) {
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
