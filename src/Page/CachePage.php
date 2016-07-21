<?php namespace ShvetsGroup\JetPages\Page;

use Illuminate\Contracts\Cache\Repository as Cache;

class CachePage implements Pageable {
    use PageTrait;

    private $cache;
    private $attributes;

    public function __construct(array $attributes = [], Cache $cache)
    {
        $this->cache = $cache;
        $this->attributes = $attributes ?: [];
    }

    /**
     * Helper to get attribute.
     *
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function getAttribute($key)
    {
        return array_get($this->attributes, $key, $default);
    }

    /**
     * Helper to set attribute.
     *
     * @param $key
     * @param $value
     */
    public function setAttribute($key, $value)
    {
        array_set($this->attributes, $key, $value);
    }

    /**
     * Remove a key from attributes.
     *
     * @param $key
     */
    public function removeAttribute($key)
    {
        if (!isset($this->{$key})) {
            return;
        }
        unset($this->attributes[$key]);
    }

    /**
     * Create a new page object.
     *
     * @param array $attributes
     * @return static
     */
    private static function create(array $attributes = []) {
        return new static($attributes, app('cache.store'));
    }

    /**
     * Convert page to array.
     * @return array
     */
    public function toArray()
    {
        return $this->attributes ?: [];
    }

    /**
     * Save page back to cache.
     */
    public function save(array $options = []) {
        if ($slug = $this->getAttribute('slug')) {
            $this->cache->forever("jetpage:{$slug}", $this->toArray());
        }
    }

    /**
     * Fill the page with an array of attributes.
     * @param array $attributes
     */
    public function fill(array $attributes = []) {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Find a page by string uri.
     *
     * @param $uri
     * @return null|CachePage
     */
    public function findByUri($uri) {
        $slug = $this->uriToSlug($uri);
        $page = $this->cache->get("jetpage:{$slug}");
        if ($page) {
            return $this->create($page);
        }
        else {
            return null;
        }
    }

    /**
     * Get the time of last page update.
     * @return int
     */
    public function lastUpdatedTime()
    {
        return $this->cache->get("jetpage_last_updated", 0);
    }

    /**
     * Dynamically retrieve attributes on the page.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the page.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute or relation exists on the page.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return ! is_null($this->getAttribute($key));
    }

    /**
     * Unset an attribute on the page.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }
}