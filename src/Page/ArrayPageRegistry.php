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
            $this->pages[$page->localeSlug()] = $page;
        }
    }

    /**
     * Reset pages list.
     */
    public function reset()
    {
        $this->pages = [];
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
     * Load a page by its locale and slug pair.
     *
     * @param $locale
     * @param $slug
     * @return Page
     */
    public function findBySlug($locale, $slug)
    {
        return $this->pages[Page::makeLocaleSlug($locale, $slug)] ?? null;
    }

    /**
     * Write page data to repository.
     * @param Page $page
     * @return Page
     */
    protected function write(Page $page)
    {
        $this->pages[$page->localeSlug()] = $page;
        return $page;
    }

    /**
     * Scratch page data to repository.
     * @param string $localeSlug
     */
    protected function scratch($localeSlug)
    {
        unset($this->pages[$localeSlug]);
    }
}