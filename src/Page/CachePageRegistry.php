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
        $localeSlug = Page::makeLocaleSlug($locale, $slug);

        $page = $this->cache->get("jetpage:{$localeSlug}");

        if ($page) {
            return new Page($page, $this->cache);
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
     * @return int
     */
    public function lastUpdatedTime()
    {
        $time = $this->cache->get("jetpage_last_updated", 0);
        if (!$time) {
            $time = parent::lastUpdatedTime();
            $this->cache->forever("jetpage_last_updated", $time);
        }
        return $time;
    }

    /**
     * Write page data to repository.
     * @param Page $page
     * @return Page
     */
    protected function write(Page $page)
    {
        $localeSlug = $page->localeSlug();
        $this->updateIndex($localeSlug);
        $this->cache->forever("jetpage:$localeSlug", $page->toArray());
        $this->cache->forget("jetpage_last_updated");
        return $page;
    }

    /**
     * Scratch page data to repository.
     * @param string $localeSlug
     */
    protected function scratch($localeSlug)
    {
        $this->updateIndex($localeSlug, true);
        $this->cache->forget("jetpage:$localeSlug");
        $this->cache->forget("jetpage_last_updated");
    }

    /**
     * Add or remove a page from index.
     *
     * @param $slug
     * @param bool $delete
     */
    private function updateIndex($slug, $delete = false) {
        $index = $this->cache->get("jetpage_index", []);
        if ($delete) {
            $index = array_diff($index, [$slug]);
        }
        else {
            $index[] = $slug;
        }
        $this->cache->forever("jetpage_index", $index);
    }
}