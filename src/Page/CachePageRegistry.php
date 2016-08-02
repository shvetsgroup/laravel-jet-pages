<?php namespace ShvetsGroup\JetPages\Page;

class CachePageRegistry extends AbstractPageRegistry
{
    /**
     * @var \Illuminate\Contracts\Cache\Store
     */
    private $cache;

    public function __construct()
    {
        $this->cache = app('cache.store')->tags('jetpages');
    }

    /**
     * Clear all generated content.
     */
    public function reset()
    {
        $this->cache->flush();
    }

    /**
     * Load a page by its locale and slug pair.
     *
     * @param $locale
     * @param $slug
     * @return Page
     */
    public function findBySlug($locale, $slug)
    {
        $localeSlug = $this->makeLocaleSlug($locale, $slug);
        $page = $this->cache->get("jetpage:{$localeSlug}");
        if ($page) {
            return new CachePage($page, $this->cache);
        } else {
            return null;
        }
    }

    /**
     * Get the array of all page slugs.
     * @return string[]
     */
    public function index()
    {
        return $this->cache->get("jetpage_index", []);
    }

    /**
     * Get (or set) the time of last page update.
     * @param null $time
     * @return int
     */
    public function lastUpdatedTime($time = null)
    {
        if ($time != null) {
            $this->cache->forever("jetpage_last_updated", $time);
            return $time;
        }
        return $this->cache->get("jetpage_last_updated", 0);
    }
}