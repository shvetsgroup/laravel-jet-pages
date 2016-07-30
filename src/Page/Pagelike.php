<?php namespace ShvetsGroup\JetPages\Page;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Defines base operations with a page.
 */
interface Pagelike extends Arrayable
{
    /**
     * Get the array of all page slugs.
     * @return string[]
     */
    public function index();

    /**
     * Create a new page object.
     * @param array $attributes
     * @return Pagelike
     */
    public function createAndSave(array $attributes = []);

    /**
     * @param array $attributes
     * @return Pagelike
     */
    public function fill(array $attributes);

    /**
     * @param array $options
     * @return Pagelike
     */
    public function save(array $options = []);

    /**
     * @return Pagelike
     */
    public function delete();

    /**
     * @return mixed
     */
    public function getAttribute($key);

    /**
     * @param string $key
     * @param mixed $value
     * @return Pagelike
     */
    public function setAttribute($key, $value);

    /**
     * @param string $key
     * @return Pagelike
     */
    public function removeAttribute($key);

    /**
     * @param $uri
     * @return Pagelike
     */
    public function findByUri($uri);

    /**
     * @param $uri
     * @return Pagelike
     */
    public function findByUriOrFail($uri);

    /**
     * @return int
     */
    public function lastUpdatedTime();
}