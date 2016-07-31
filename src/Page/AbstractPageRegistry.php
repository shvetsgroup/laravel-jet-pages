<?php namespace ShvetsGroup\JetPages\Page;

abstract class AbstractPageRegistry implements PageRegistry
{
    use PageTrait;

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
     * @param $slug
     * @param array $fields
     * @return mixed
     */
    public function getPageData($slug, array $fields) {
        $page = $this->findByUri($slug);
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
        foreach ($index as $slug) {
            $all[$slug] = $this->findByUri($slug);
        }
        return $all;
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
}