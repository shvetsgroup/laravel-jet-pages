<?php namespace ShvetsGroup\JetPages\Page;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Defines base operations with a page.
 */
interface Page extends Arrayable
{
    /**
     * @param array $attributes
     * @return Page
     */
    public function fill(array $attributes);

    /**
     * @param array $options
     * @return Page
     */
    public function save(array $options = []);

    /**
     * @return Page
     */
    public function delete();

    /**
     * @param $key
     * @return mixed
     */
    public function getAttribute($key);

    /**
     * @param string $key
     * @param mixed $value
     * @return Page
     */
    public function setAttribute($key, $value);

    /**
     * @param string $key
     * @return Page
     */
    public function removeAttribute($key);
}