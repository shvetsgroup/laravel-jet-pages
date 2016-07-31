<?php namespace ShvetsGroup\JetPages\Page;

class CachePageRegistry extends AbstractPageRegistry
{
    private $cache;

    public function __construct()
    {
        $this->cache = app('cache.store');
    }

    /**
     * Find a page by string uri.
     *
     * @param $uri
     * @return null|Page
     */
    public function findByUri($uri)
    {
        $slug = $this->uriToSlug($uri);
        $page = $this->cache->get("jetpage:{$slug}");
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