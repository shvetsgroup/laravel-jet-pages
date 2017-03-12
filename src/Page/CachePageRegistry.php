<?php namespace ShvetsGroup\JetPages\Page;

class CachePageRegistry extends SimplePageRegistry
{
    /**
     * @var \Illuminate\Contracts\Cache\Store
     */
    private $cache;

    public function __construct(array $pages = [])
    {
        parent::__construct($pages);
        $this->cache = app('cache.store');
    }

    /**
     * Get the array of all page objects.
     * @return Page[]
     */
    public function getAll()
    {
        if ($pages = parent::getAll()) {
            return $pages;
        }

        $index = $this->index();
        $this->pages = [];
        foreach ($index as $localeSlug => $updated_at) {
            $page = $this->findBySlug('', $localeSlug);
            $this->add($page);
        }
        return $this->pages;
    }

    /**
     * Clear all generated content.
     */
    public function reset()
    {
        parent::reset();
        $this->cache->flush();
    }

    /**
     * Get the array of all page slugs.
     * @return string[]
     */
    public function index()
    {
        if ($index = parent::index()) {
            return $index;
        }

        $this->index = $this->cache->get("jetpage_index", []);
        return $this->index;
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
        if ($page = parent::findBySlug($locale, $slug)) {
            return $page;
        }

        $localeSlug = Page::makeLocaleSlug($locale, $slug);
        $data = $this->cache->get("jetpage:{$localeSlug}");

        if ($data) {
            $page = new Page($data);
            return $page;
        } else {
            return null;
        }
    }

    /**
     * Get (or set) the time of last page update.
     * @return string
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
    public function save(Page $page)
    {
        parent::save($page);
        $localeSlug = $page->localeSlug();
        $this->updateIndex($localeSlug, $page->updated_at);
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
        parent::scratch($localeSlug);
        $this->updateIndex($localeSlug, 0, true);
        $this->cache->forget("jetpage:$localeSlug");
        $this->cache->forget("jetpage_last_updated");
    }

    /**
     * Add or remove a page from index.
     *
     * @param $localeSlug
     * @param bool $delete
     */
    private function updateIndex($localeSlug, $time = 0, $delete = false) {
        $index = $this->cache->get("jetpage_index", []);
        if ($delete) {
            unset($index[$localeSlug]);
        }
        else {
            $index[$localeSlug] = $time;
        }
        $this->cache->forever("jetpage_index", $index);
        $this->index = $index;
    }
}