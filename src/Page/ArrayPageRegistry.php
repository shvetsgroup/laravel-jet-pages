<?php namespace ShvetsGroup\JetPages\Page;

class ArrayPageRegistry extends AbstractPageRegistry
{
    /**
     * @var Page[]
     */
    private $pages = [];

    public function __construct(array $pages = [])
    {
        foreach ($pages as $page) {
            $this->pages[$page->getAttribute('slug')] = $page;
        }
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
        return $this->pages[$slug];
    }

    /**
     * Get the array of all page slugs.
     * @return string[]
     */
    public function index()
    {
        return array_keys($this->pages);
    }

    /**
     * Get the array of all page objects.
     * @return Page[]
     */
    public function getAll()
    {
        return $this->pages;
    }

    /**
     * Get (or set) the time of last page update.
     * @return int
     */
    public function lastUpdatedTime()
    {
        $max = 0;
        foreach ($this->pages as $page) {
            $updated_at = $page->getAttribute('updated_at');
            $max = $updated_at > $max ? $updated_at : $max;
        }
        return $max;
    }

    /**
     * Reset pages list.
     */
    public function reset()
    {
        $this->pages = [];
    }

    /**
     * Add a page (or pages) to the repo.
     * @param Page|Page[] $pages
     */
    public function add($pages)
    {
        if (!$pages) {
            return;
        }
        if (!is_array($pages)) {
            $this->pages[$pages->getAttribute('slug')] = $pages;
        } else {
            foreach ($pages as $page) {
                $this->pages[$page->getAttribute('slug')] = $page;
            }
        }
    }

    /**
     * Save all page objects.
     */
    public function save()
    {
        foreach ($this->pages as $page) {
            $page->save();
        }
    }
}