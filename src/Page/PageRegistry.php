<?php namespace ShvetsGroup\JetPages\Page;

/**
 * Defines page registry operations.
 */
interface PageRegistry
{
    /**
     * Clear all generated content.
     */
    public function reset();

    /**
     * Get the array of all page slugs.
     * @return string[]
     */
    public function index();

    /**
     * Get the array of all page objects.
     * @return Page[]
     */
    public function getAll();

    /**
     * Get the array of public page objects.
     * @return Page[]
     */
    public function getPublic();

    /**
     * Create a new page object.
     * @param array $attributes
     * @return Page
     */
    public function new(array $attributes = []);

    /**
     * Create a new page object and save it.
     * @param array $attributes
     * @return Page
     */
    public function createAndSave(array $attributes = []);

    /**
     * Make sure page timestamps are set.
     * @param Page $page
     * @return $this
     */
    public function prepare(Page $page);

    /**
     * Import pages from other registry.
     * @param PageRegistry|array $registry
     */
    public function import($registry);

    /**
     * Temporarily add a page to repository. You need to call saveAll to persist them.
     * @param Page $page
     * @return Page
     */
    public function add(Page $page);

    /**
     * Temporarily add pages to repository. You need to call saveAll to persist them.
     * @param array $pages
     * @return $this
     */
    public function addAll(array $pages);

    /**
     * @param Page $page
     * @return Page
     */
    public function save(Page $page);

    /**
     * @return $this
     */
    public function saveAll();

    /**
     * @return mixed
     */
    public function updateIndexes();

    /**
     * @param Page $page
     * @return $this
     */
    public function delete(Page $page);

    /**
     * Load a set of fields values from a page by its slug.
     *
     * @param $locale
     * @param $slug
     * @param $field
     * @return mixed
     */
    public function getPageField($locale, $slug, $field);

    /**
     * Load a set of fields values from a page by its slug.
     *
     * @param $locale
     * @param $slug
     * @param array $fields
     * @return mixed
     */
    public function getPageData($locale, $slug, array $fields);

    /**
     * Load all pages by their field value.
     *
     * @param string|array $key
     * @param $value
     * @return Page[]
     */
    public function findAllBy($key, $value = null);

    /**
     * Load a first page by its field value.
     *
     * @param string|array $key
     * @param $value
     * @return Page
     */
    public function findFirstBy($key, $value = null);

    /**
     * Load a page by its locale and slug pair.
     *
     * @param $locale
     * @param $slug
     * @return Page
     */
    public function findBySlug($locale, $slug);

    /**
     * Load a page by its uri, parsed into locale and slug.
     *
     * @param $uri
     * @return Page
     */
    public function findByUri($uri);

    /**
     * Same as @findByUri, but returns 404 error if no page found.
     *
     * @param $uri
     * @return Page
     */
    public function findByUriOrFail($uri);

    /**
     * Check whether a search index exists.
     * @param $columns
     * @return bool
     */
    public function hasSearchIndex($columns);

    /**
     * Create a search index.
     * @param $columns
     * @param $caseSensitive
     */
    public function makeSearchIndex($columns);

    /**
     * Find page in search index.
     *
     * @param $name
     * @param $key
     * @return Page|null
     */
    public function findInSearchIndex($name, $key);

    /**
     * @return string
     */
    public function lastUpdatedTime();

    /**
     * @return string
     */
    public function lastBuildTime();

    /**
     * @return string
     */
    public function updateBuildTime();

    /**
     * @param Page $freshPage
     * @return bool
     */
    public function needsUpdate(Page $freshPage);
}