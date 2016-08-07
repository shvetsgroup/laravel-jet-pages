<?php namespace ShvetsGroup\JetPages\Page;

use Carbon\Carbon;

abstract class AbstractPageRegistry implements PageRegistry
{
    /**
     * Import pages from other registry.
     * @param PageRegistry|array $registry
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
        $result = [];
        foreach ($this->getAll() as $page) {
            $result[$page->localeSlug()] = $page->updated_at;
        }
        return $result;
    }

    /**
     * Create a new page object.
     *
     * @param array $attributes
     * @return Page
     */
    public function new(array $attributes = [])
    {
        return new Page($attributes);
    }

    /**
     * Create a new page object and save it.
     *
     * @param array $attributes
     * @return Page
     */
    public function createAndSave(array $attributes = [])
    {
        return $this->save($this->new($attributes));
    }

    /**
     * Make sure page ready for saving.
     * @param Page $page
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
     * @return int
     */
    public function lastUpdatedTime()
    {
        $max = 0;
        foreach ($this->getAll() as $page) {
            $updated_at = $page->getAttribute('updated_at');
            $max = $updated_at > $max ? $updated_at : $max;
        }
        return $max;
    }

    /**
     * Check if repository has an older version of a page.
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
     * @param array $fields
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
     * Get the array of all page objects.
     * @return Page[]
     */
    public function getAll()
    {
        $index = $this->index();
        $all = [];
        foreach ($index as $localeSlug => $updated_at) {
            $all[$localeSlug] = $this->findBySlug('', $localeSlug);
        }
        return $all;
    }

    /**
     * Find a page by string uri.
     *
     * @param $uri
     * @return null|Page
     */
    public function findByUri($uri)
    {
        list($locale, $slug) = Page::uriToLocaleSlugArray($uri);
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
     * @param string|array $key
     * @param $value
     * @param array $pages
     * @return Page[]
     */
    public function findAllBy($key, $value = null, $pages = [])
    {
        return $this->findBy($key, $value);
    }

    /**
     * Load a first page by its field value.
     *
     * @param string|array $key
     * @param $value
     * @param array $pages
     * @return Page
     */
    public function findFirstBy($key, $value = null, $pages = [])
    {
        return $this->findBy($key, $value, 1, $pages);
    }

    /**
     * @param string|array $key
     * @param null $value
     * @param bool $returnSingle
     * @param array $pages
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
            $allTrue = true;
            foreach ($key as $k => $v) {
                if ($page->getAttribute($k) != $v) {
                    $allTrue = false;
                    break;
                }
            }
            if ($allTrue) {
                if ($returnSingle) {
                    return $page;
                } else {
                    $results[$localeSlug] = $page;
                }
            }
        }
        return $results;
    }

    /**
     * Save page back to cache.
     * @param Page $page
     * @return Page
     */
    public function save(Page $page)
    {
        $this->removeOld($page);
        $this->prepare($page);
        $this->write($page);
        return $page;
    }

    /**
     * Remove old page from the store and index.
     *
     * @param Page $page
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
     * @param Page $page
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
     * Write page data to repository.
     * @param Page $page
     * @return Page
     */
    abstract protected function write(Page $page);

    /**
     * Scratch page data to repository.
     * @param string $localeSlug
     */
    abstract protected function scratch($localeSlug);
}