<?php namespace ShvetsGroup\JetPages\Builders\Parsers;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

class LocaleParser implements Parser
{
    /**
     * @var \Mcamara\LaravelLocalization\LaravelLocalization
     */
    private $laravellocalization;

    public function __construct()
    {
        if (app()->bound('laravellocalization')) {
            $this->laravellocalization = app('laravellocalization');
        }
    }

    /**
     * @param Page $page
     * @param PageRegistry $registry
     */
    public function parse(Page $page, PageRegistry $registry)
    {
        if (!$this->laravellocalization) {
            return;
        }

        list($locale, $slug) = Page::extractLocale($page->getAttribute('slug'));

        if ($this->isValidLocale($locale)) {
            $page->setAttribute('locale', $locale);
            $page->setAttribute('slug', $slug);
        }
    }

    public function isValidLocale($locale)
    {
        $locales = $this->laravellocalization->getSupportedLocales();
        return isset($locales[$locale]);
    }
}