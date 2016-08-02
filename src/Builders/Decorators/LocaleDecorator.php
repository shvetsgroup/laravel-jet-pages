<?php namespace ShvetsGroup\JetPages\Builders\Decorators;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;
use ShvetsGroup\JetPages\Page\PageTrait;

class LocaleDecorator implements Decorator
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
    public function decorate(Page $page, PageRegistry $registry)
    {
        if (!$this->laravellocalization) {
            return;
        }

        list($locale, $slug) = PageTrait::extractLocale($page->getAttribute('slug'));

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