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
     * Import pages from other registry.
     * @param PageRegistry $registry
     */
    public function import(PageRegistry $registry);

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
     * @return int
     */
    public function lastUpdatedTime();
}