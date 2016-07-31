<?php namespace ShvetsGroup\JetPages\Page;

/**
 * Defines page registry operations.
 */
interface PageRegistry
{
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
     * @param $uri
     * @return Page
     */
    public function findByUri($uri);

    /**
     * @param $uri
     * @return Page
     */
    public function findByUriOrFail($uri);

    /**
     * @return int
     */
    public function lastUpdatedTime();
}