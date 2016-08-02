<?php namespace ShvetsGroup\JetPages\Page;

abstract class AbstractPageRegistry implements PageRegistry
{
    use PageTrait;

    /**
     * Import pages from other registry.
     * @param PageRegistry $registry
     */
    public function import(PageRegistry $registry)
    {
        foreach ($registry->getAll() as $page) {
            $page->save();
        }
    }

    /**
     * Create a new page object.
     *
     * @param array $attributes
     * @return Page
     */
    public function new(array $attributes = [])
    {
        return app()->make('page', [$attributes]);
    }

    /**
     * Create a new page object and save it.
     *
     * @param array $attributes
     * @return Page
     */
    public function createAndSave(array $attributes = [])
    {
        return $this->new($attributes)->save();
    }

    /**
     * Load a set of fields values from a page by its slug.
     *
     * @param $locale
     * @param $slug
     * @param array $fields
     * @return mixed
     */
    public function getPageData($locale, $slug, array $fields) {
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
        foreach ($index as $localeSlug) {
            $all[$localeSlug] = $this->findByUri($localeSlug);
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
        list($locale, $slug) = $this->uriToLocaleSlugArray($uri);
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
     * @return Page[]
     */
    public function findAllBy($key, $value = null)
    {
        return $this->findBy($key, $value);
    }

    /**
     * Load a first page by its field value.
     *
     * @param string|array $key
     * @param $value
     * @return Page
     */
    public function findFirstBy($key, $value = null)
    {
        return $this->findBy($key, $value, 1);
    }

    /**
     * @param string|array $key
     * @param null $value
     * @param bool $returnSingle
     * @return array|Page
     */
    private function findBy($key, $value = null, $returnSingle = false) {
        $results = [];
        if (!is_array($key)) {
            $key = [$key => $value];
        }
        foreach ($this->getAll() as $localeSlug => $page) {
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
                }
                else {
                    $results[$localeSlug] = $page;
                }
            }
        }
        return $results;
    }
}