<?php namespace ShvetsGroup\JetPages\Page;

class CachePage implements Page
{
    use PageTrait;

    private $cache;
    private $attributes;

    public function __construct(array $attributes = [])
    {
        $this->cache = app('cache.store');
        $this->setRawAttributes($attributes);
    }

    /**
     * Convert page to array.
     * @param array $attributes
     * @return $this
     */
    public function setRawAttributes(array $attributes = [])
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Helper to get attribute.
     *
     * @param $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        return array_get($this->attributes, $key);
    }

    /**
     * Helper to set attribute.
     *
     * @param string $key
     * @param mixed $value
     * @param bool $force
     * @return $this
     */
    public function setAttribute($key, $value, $force = false)
    {
        if (($key == 'created_at' || $key == 'updated_at') && is_object($value)) {
            $value = $value->timestamp;
        }
        if ($key == 'slug' && !$force) {
            array_set($this->attributes, 'old_slug', array_get($this->attributes, 'slug'));
        }
        array_set($this->attributes, $key, $value);
        return $this;
    }

    /**
     * Remove a key from attributes.
     *
     * @param $key
     * @return $this
     */
    public function removeAttribute($key)
    {
        if (isset($this->attributes[$key])) {
            unset($this->attributes[$key]);
        }
        return $this;
    }

    /**
     * Save page back to cache.
     * @param array $options
     * @return $this
     */
    public function save(array $options = [])
    {
        $slug = $this->checkSlug();
        $old_slug = $this->checkSlug('old_slug', false);
        if ($old_slug) {
            $this->removeAttribute('old_slug');
        }
        $this->updateTimestamps();
        $this->cache->forever("jetpage:{$slug}", $this->toArray());
        $this->updateIndex($slug, $old_slug);
        return $this;
    }

    /**
     * Make sure timestamps are set.
     */
    protected function updateTimestamps()
    {
        if (!$this->getAttribute('created_at')) {
            $this->setAttribute('created_at', time());
        }
        $this->setAttribute('updated_at', time());
    }

    /**
     * Remove the page.
     * @return $this
     */
    public function delete()
    {
        $slug = $this->checkSlug();
        $old_slug = $this->checkSlug('old_slug', false);
        $this->cache->forget("jetpage:{$slug}");
        $this->updateIndex($slug, $old_slug, true);
        return $this;
    }

    /**
     * Add or remove a page from index.
     *
     * @param $slug
     * @param null $old_slug
     * @param bool $delete
     */
    private function updateIndex($slug, $old_slug = null, $delete = false) {
        $index = $this->cache->get("jetpage_index", []);

        if ($delete) {
            $index = array_diff($index, [$old_slug ? $old_slug : $slug]);
        }
        else {
            if ($old_slug) {
                $index = array_diff($index, [$old_slug]);
            }
            $index[] = $slug;
        }

        $this->cache->forever("jetpage_index", $index);
        $this->cache->forever("jetpage_last_updated", time());
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
     * Fill the page with an array of attributes.
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    /**
     * Dynamically retrieve attributes on the page.
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the page.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute or relation exists on the page.
     *
     * @param  string $key
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->getAttribute($key));
    }

    /**
     * Unset an attribute on the page.
     *
     * @param  string $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
}