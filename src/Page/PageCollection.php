<?php

namespace ShvetsGroup\JetPages\Page;

use Illuminate\Support\Collection;

class PageCollection extends Collection
{
    /**
     * @var PageUtils
     */
    private $pageUtils;

    private $localeTitleIndex = null;

    /**
     * @param  Collection  $records
     * @param  bool  $markExisting
     * @return static
     */
    public static function fromRecords(Collection $records, $markExisting = true)
    {
        $pages = new PageCollection();
        foreach ($records as $record) {
            $page = new Page((array) $record);
            if ($markExisting) {
                $page->exists = true;
            }
            $pages->addPage($page);
        }
        return $pages;
    }

    public function delete($localeSlug)
    {
        $page = $this->get($localeSlug);
        if ($page) {
            $page->delete();
        }
        $this->forget($localeSlug);
    }

    public function addNewPage($attributes)
    {
        $page = new Page($attributes);
        $this->addPage($page);
        return $page;
    }

    public function addPage(Page $page)
    {
        $this->put($page->getAttribute('localeSlug'), $page);
        return $page;
    }

    public function findBySlug($locale, $slug)
    {
        $this->pageUtils = $this->pageUtils ?? app('page.utils');
        return $this->get($this->pageUtils->makeLocaleSlug($locale, $slug));
    }

    public function findByLocaleTitle($locale, $title)
    {
        if ($this->localeTitleIndex === null) {
            $this->updateLocaleTitleIndex();
        }

        if (isset($this->localeTitleIndex[$locale][$title])) {
            $localeSlug = $this->localeTitleIndex[$locale][$title];
            return $this->get($localeSlug);
        }
    }

    private function updateLocaleTitleIndex()
    {
        $this->localeTitleIndex = [];
        foreach ($this->getIterator() as $page) {
            $localeSlug = $page->getAttribute('localeSlug');
            $locale = $page->getAttribute('locale');
            $title = $page->getAttribute('title');

            if (!isset($this->localeTitleIndex[$locale])) {
                $this->localeTitleIndex[$locale] = [];
            }

            $this->localeTitleIndex[$locale][$title] = $localeSlug;
        }
    }

    public function saveAll()
    {
        foreach ($this->items as $page) {
            $page->save();
        }
    }

    public function makePagesUseCollectionInsteadOfQuery()
    {
        foreach ($this->items as $page) {
            $page->_pages = $this;
        }
    }
}