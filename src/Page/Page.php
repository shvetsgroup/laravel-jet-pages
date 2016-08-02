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
     * Get the locale / slug string.
     * This should be the same for [locale => 'en', 'slug' => 'test'] and [locale => '', 'slug' => 'en/test']
     * @return string
     */
    public function localeSlug();

    /**
     * Generate page uri.
     * @return string
     */
    public function uri();

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