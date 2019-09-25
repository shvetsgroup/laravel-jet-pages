<?php

namespace ShvetsGroup\JetPages\Page;

use Carbon\Carbon;
use ShvetsGroup\JetPages\Facades\PageUtils;

class SimplePageRegistry implements PageRegistry
{
    /**
     * @var Page[]
     */
    protected $pages = [];
    protected $index = [];
    protected $searchIndexes = [];

    public function __construct(array $pages = [])
    {
        $this->addAll($pages);
    }

    /**
     * Reset pages list.
     */
    public function reset()
    {
        $this->pages = [];
    }

    /**
     * Get the array of all page objects.
     * @return Page[]
     */
    public function getAll()
    {
        return $this->pages;
    }

    /**
     * Get the array of public page objects.
     * @return Page[]
     */
    public function getPublic()
    {
        return $this->findAllBy('private', false);
    }

    /**
     * Import pages from other registry.
     * @param  PageRegistry|array  $registry
     */
    public function import($registry)
    {
        if (is_array($registry)) {
            $pages = $registry;
        } else {
            $pages = $registry->getAll();
        }
        foreach ($pages as $page) {
            $this->save($page);
        }
    }

    /**
     * Get the array of all page slugs.
     * @return string[]
     */
    public function index()
    {
        return $this->index;
    }

    /**
     * Create a new page object.
     *
     * @param  array  $attributes
     * @return Page
     */
    public function new(array $attributes = []): Page
    {
        return new Page($attributes);
    }

    /**
     * Create a new page object and save it.
     *
     * @param  array  $attributes
     * @return Page
     */
    public function createAndSave(array $attributes = [])
    {
        return $this->save($this->new($attributes));
    }

    /**
     * Make sure page ready for saving.
     * @param  Page  $page
     * @return $this
     */
    public function prepare(Page $page)
    {
        if (!$page->getAttribute('created_at')) {
            $page->setAttribute('created_at', Carbon::now()->format('Y-m-d H:i:s'));
        }
        if (!$page->getAttribute('updated_at')) {
            $page->setAttribute('updated_at', Carbon::now()->format('Y-m-d H:i:s'));
        }
        return $this;
    }

    /**
     * Get (or set) the time of last page update.
     * @return string
     */
    public function lastUpdatedTime()
    {
        $index = $this->index();
        if (count($index)) {
            $array = array_values($index);
            arsort($array);
            return $array[0];
        }
        return '0';
    }

    /**
     * Check if repository has an older version of a page.
     * @param  Page  $page
     * @return bool
     */
    public function needsUpdate(Page $page)
    {
        $index = $this->index();
        $localeSlug = $page->localeSlug();
        $current = $index[$localeSlug] ?? null;
        return !$current || $page->updated_at > $current;
    }

    /**
     * Load a set of fields values from a page by its slug.
     *
     * @param $locale
     * @param $slug
     * @param $field
     * @return mixed
     */
    public function getPageField($locale, $slug, $field)
    {
        $page = $this->findBySlug($locale, $slug);
        return $page->getAttribute($field);
    }

    /**
     * Load a set of fields values from a page by its slug.
     *
     * @param $locale
     * @param $slug
     * @param  array  $fields
     * @return mixed
     */
    public function getPageData($locale, $slug, array $fields)
    {
        $page = $this->findBySlug($locale, $slug);
        $result = [];
        foreach ($fields as $field) {
            $result[$field] = $page->getAttribute($field);
        }
        return $result;
    }

    /**
     * Load a page by its locale and slug pair.
     *
     * @param $locale
     * @param $slug
     * @return Page
     */
    public function findBySlug($locale, $slug)
    {
        return $this->pages[PageUtils::makeLocaleSlug($locale, $slug)] ?? null;
    }

    /**
     * Find a page by string uri.
     *
     * @param $uri
     * @return null|Page
     */
    public function findByUri($uri)
    {
        list($locale, $slug) = PageUtils::uriToLocaleSlugArray($uri);
        return $this->findBySlug($locale, $slug);
    }

    /**
     * Load page object by its uri.
     *
     * @param $uri
     * @return mixed
     */
    public function findByUriOrFail($uri)
    {
        $page = $this->findByUri($uri);
        if (!$page) {
            return abort(404);
        }
        return $page;
    }

    /**
     * Load all pages by their field value.
     *
     * @param  string|array  $key
     * @param $value
     * @param  array  $pages
     * @return Page[]
     */
    public function findAllBy($key, $value = null, $pages = [])
    {
        return $this->findBy($key, $value);
    }

    /**
     * Load a first page by its field value.
     *
     * @param  string|array  $key
     * @param $value
     * @param  array  $pages
     * @return Page
     */
    public function findFirstBy($key, $value = null, $pages = [])
    {
        return $this->findBy($key, $value, 1, $pages);
    }

    /**
     * @param  string|array  $key
     * @param  null  $value
     * @param  bool  $returnSingle
     * @param  array  $pages
     * @return array|Page
     */
    protected function findBy($key, $value = null, $returnSingle = false, $pages = [])
    {
        $results = [];
        if (!is_array($key)) {
            $key = [$key => $value];
        }
        $pages = $pages ?: $this->getAll();
        foreach ($pages as $localeSlug => $page) {
            foreach ($key as $k => $v) {
                if ($page->getAttribute($k) != $v) {
                    continue 2;
                }
            }
            if ($returnSingle) {
                return $page;
            } else {
                $results[$localeSlug] = $page;
            }
        }
        return $results;
    }

    /**
     * Check whether a search index exists.
     * @param $columns
     * @return bool
     */
    public function hasSearchIndex($columns)
    {
        $name = join('__', $columns);
        return isset($this->searchIndexes[$name]);
    }

    /**
     * Create a search index.
     * @param $columns
     * @param $caseSensitive
     */
    public function makeSearchIndex($columns)
    {
        $name = join('__', $columns);
        foreach ($this->getAll() as $page) {
            $key = $this->makeSearchIndexKey($columns, $page);
            $this->searchIndexes[$name][$key] = $page;
        }
    }

    /**
     * Make a search index key.
     *
     * @param $columns
     * @param  Page  $page
     * @param $caseSensitive
     * @return string
     */
    protected function makeSearchIndexKey($columns, Page $page)
    {
        $parts = [];
        foreach ($columns as $column) {
            $part = $page->getAttribute($column);
            $parts[] = mb_strtolower(trim($part));
        }
        return join('__', $parts);
    }

    /**
     * Find page in search index.
     *
     * @param $name
     * @param $key
     * @return Page|null
     */
    public function findInSearchIndex($name, $key, $caseSensitive = false)
    {
        $key = mb_strtolower(trim($key));
        return $this->searchIndexes[$name][$key] ?? null;
    }

    /**
     * Save page back to cache.
     * @param  Page  $page
     * @return Page
     */
    public function save(Page $page)
    {
        $this->removeOld($page);
        $this->prepare($page);
        $this->add($page);
        return $page;
    }

    /**
     * Save all pages back to cache.
     */
    public function saveAll()
    {
        foreach ($this->pages as $page) {
            $this->save($page);
        }
        return $this;
    }

    /**
     * Remove old page from the store and index.
     *
     * @param  Page  $page
     * @return string
     */
    private function removeOld(Page $page)
    {
        $oldLocaleSlug = $page->localeSlug('oldSlug');
        if ($oldLocaleSlug) {
            $page->removeAttribute('oldSlug');
            $this->scratch($oldLocaleSlug);
        }
        return $oldLocaleSlug;
    }

    /**
     * Remove the page.
     * @param  Page  $page
     * @return $this
     */
    public function delete(Page $page)
    {
        if (!$this->removeOld($page)) {
            $localeSlug = $page->localeSlug();
            $this->scratch($localeSlug);
        }
        return $this;
    }

    /**
     * Update all indexes from existing pages.
     *
     * @return mixed|void
     * @throws PageException
     */
    function updateIndexes()
    {
        foreach ($this->pages as $localeSlug => $page) {
            if ($localeSlug !== $page->localeSlug()) {
                $this->add($page);
                $this->scratch($localeSlug);
            }
        }
    }

    /**
     * Temporarily add a page to repository. You need to call saveAll to persist them.
     * @param  Page  $page
     * @return Page
     */
    public function add(Page $page)
    {
        $localeSlug = $page->localeSlug();
        $this->pages[$localeSlug] = $page;
        $this->index[$localeSlug] = $page->updated_at;
        return $page;
    }

    /**
     * Temporarily add pages to repository. You need to call saveAll to persist them.
     * @param  array  $pages
     * @return $this
     */
    public function addAll(array $pages)
    {
        foreach ($pages as $page) {
            $this->add($page);
        }
    }

    /**
     * Scratch page data to repository.
     * @param  string  $localeSlug
     */
    protected function scratch($localeSlug)
    {
        unset($this->pages[$localeSlug]);
        unset($this->index[$localeSlug]);
    }

    /**
     * Get (or set) the time of last page update.
     * @return string
     */
    public function lastBuildTime()
    {
        $timestampFile = storage_path('app/content_timestamps/build.json');
        if (file_exists($timestampFile)) {
            $time = json_decode(file_get_contents($timestampFile));
        } else {
            $time = 0;
        }
        return $time;
    }

    /**
     * Get (or set) the time of last page update.
     * @return string
     */
    public function updateBuildTime()
    {
        $time = date('Y-m-d H:i:s');
        $files = app('Illuminate\Filesystem\Filesystem');
        $files->makeDirectory(storage_path('app/content_timestamps'), 0755, true, true);
        $files->put(storage_path('app/content_timestamps/build.json'), json_encode($time));

        return $time;
    }
}