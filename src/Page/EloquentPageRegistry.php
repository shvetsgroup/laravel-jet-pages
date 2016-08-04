<?php namespace ShvetsGroup\JetPages\Page;

class EloquentPageRegistry extends AbstractPageRegistry
{
    /**
     * Clear all generated content.
     */
    public function reset()
    {
        app('Illuminate\Database\Connection')->table('pages')->truncate();
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
        $page = EloquentPage::where('locale', $locale)->where('slug', $slug)->first();
        return $page ?: null;
    }

    /**
     * Get the array of all page slugs.
     * @return string[]
     */
    public function index()
    {
        return app('Illuminate\Database\Connection')->table('pages')->pluck('slug');
    }

    /**
     * Get the array of all page objects.
     * @return Page[]
     */
    public function getAll()
    {
        $pages = EloquentPage::all()->all();
        return $this->listByKey($pages);
    }

    /**
     * Return page list keyed with localeSlug.
     * @param $pages
     * @return array
     */
    private function listByKey($pages) {
        $result = [];
        foreach ($pages as $page) {
            $result[$page->localeSlug()] = $page;
        }
        return $result;
    }

    /**
     * Get the time of last page update.
     * @return int
     */
    public function lastUpdatedTime()
    {
        $last_updated = EloquentPage::orderBy('updated_at', 'DESC')->first();
        if ($last_updated) {
            $last_updated->fresh();
        }
        return $last_updated ? $last_updated->updated_at : 0;
    }

    /**
     * Load all pages by their field value.
     *
     * @param string|array $key
     * @param $value
     * @return Page[]
     */
    public function findAllBy($key, $value = null)
    {
        list($database_filters, $iteration_filters) = $this->makeFilters($key, $value);

        $pages = EloquentPage::where($database_filters)->get()->all();

        if (!$iteration_filters) {
            return $this->listByKey($pages);
        }

        $result = [];
        foreach ($pages as $page) {
            foreach ($iteration_filters as list($k, $v)) {
                if ($page->getAttribute($k) != $v) {
                    continue 2;
                }
            }
            $result[$page->localeSlug()] = $page;
        }
        return $result;
    }

    /**
     * Load a first page by its field value.
     *
     * @param string|array $key
     * @param $value
     * @return Page
     */
    public function findFirstBy($key, $value = null)
    {
        $result = $this->findAllBy($key, $value);
        if (is_array($result)) {
            return reset($result);
        }
        return $result;
    }

    /**
     * @param $key
     * @param null $value
     * @return mixed
     */
    private function makeFilters($key, $value = null) {
        if (is_array($key)) {
            $filters = [];
            foreach ($key as $k => $v) {
                $filters[] = [$k, $v];
            }
            $all_filters = $filters;
        }
        else {
            $all_filters = [[$key, $value]];
        }
        $database_filters = [];
        $iteration_filters = [];
        foreach ($all_filters as list($k, $v)) {
            if (in_array($k, EloquentPage::$known_fields)) {
                $database_filters[] = [$k, $v];
            }
            else {
                $iteration_filters[] = [$k, $v];
            }
        }
        return [$database_filters, $iteration_filters];
    }
}
