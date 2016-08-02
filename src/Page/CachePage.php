<?php namespace ShvetsGroup\JetPages\Page;

class CachePage implements Page
{
    use PageTrait;

    private $cache;
    private $attributes;

    public function __construct(array $attributes = [])
    {
        $this->cache = app('cache.store')->tags('jetpages');
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
            array_set($this->attributes, 'oldSlug', array_get($this->attributes, 'slug'));
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
        $locale = $this->getAttribute('locale') ?: '';
        $slug = $this->checkSlug();
        $localeSlug = $this->makeLocaleSlug($locale, $slug);

        $oldSlug = $this->checkSlug('oldSlug', false);
        $oldLocaleSlug = $this->makeLocaleSlug($locale, $oldSlug);
        if ($oldSlug) {
            $this->removeAttribute('oldSlug');
            $this->cache->forget("jetpage:$oldLocaleSlug");
        }

        $this->updateTimestamps();
        $this->cache->forever("jetpage:$localeSlug", $this->toArray());
        $this->cache->forever("jetpage_last_updated", $this->getAttribute('updated_at'));
        $this->updateIndex($localeSlug, $oldLocaleSlug);

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
        if (!$this->getAttribute('updated_at')) {
            $this->setAttribute('updated_at', time());
        }
    }

    /**
     * Remove the page.
     * @return $this
     */
    public function delete()
    {
        $slugToDelete = $this->checkSlug('oldSlug', false) ?: $this->checkSlug();
        $locale = $this->getAttribute('locale') ?: '';
        $localeSlug = $this->makeLocaleSlug($locale, $slugToDelete);

        $this->cache->forget("jetpage:{$localeSlug}");
        $this->updateIndex($localeSlug, null, true);
        $this->cache->forever("jetpage_last_updated", time());
        return $this;
    }

    /**
     * Add or remove a page from index.
     *
     * @param $slug
     * @param null $oldSlug
     * @param bool $delete
     */
    private function updateIndex($slug, $oldSlug = null, $delete = false) {
        $index = $this->cache->get("jetpage_index", []);

        if ($delete) {
            $index = array_diff($index, [$oldSlug ? $oldSlug : $slug]);
        }
        else {
            if ($oldSlug) {
                $index = array_diff($index, [$oldSlug]);
            }
            $index[] = $slug;
        }

        $this->cache->forever("jetpage_index", $index);
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