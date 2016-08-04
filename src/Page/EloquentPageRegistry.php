<?php namespace ShvetsGroup\JetPages\Page;

class EloquentPageRegistry extends AbstractPageRegistry
{
    /**
     * @var \Illuminate\Database\Connection
     */
    private $db;
    protected $db_fields = ['id', 'locale', 'slug', 'title', 'data', 'created_at', 'updated_at'];
    protected $searchable_fields = ['locale', 'slug', 'title', 'created_at', 'updated_at'];

    public function __construct()
    {
        $this->db = app('Illuminate\Database\Connection');
    }

    /**
     * Clear all generated content.
     */
    public function reset()
    {
        $this->db->table('pages')->truncate();
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
        $page = $this->db->table('pages')->where('locale', $locale)->where('slug', $slug)->first();
        return $page ? $this->fromDbRecord($page) : null;
    }

    /**
     * Get the array of all page slugs.
     * @return string[]
     */
    public function index()
    {
        $results = [];
        foreach ($this->db->table('pages')->get(['locale', 'slug']) as $record) {
            $results[] = Page::makeLocaleSlug($record->locale, $record->slug);
        }
        return $results;
    }

    /**
     * Get the array of all page objects.
     * @return Page[]
     */
    public function getAll()
    {
        $pages = $this->db->table('pages')->get();
        return $this->listRecordsByKey($pages);
    }

    /**
     * Get the time of last page update.
     * @return int
     */
    public function lastUpdatedTime()
    {
        $last_updated = $this->db->table('pages')->orderBy('updated_at', 'DESC')->first();
        return $last_updated ? $last_updated->updated_at : 0;
    }

    /**
     * @param string|array $key
     * @param null $value
     * @param bool $returnSingle
     * @param array $pages
     * @return array|Page
     */
    protected function findBy($key, $value = null, $returnSingle = false, $pages = [])
    {
        list($database_filters, $regular_filters) = $this->makeFilters($key, $value);

        $database_results = $this->db->table('pages')->where($database_filters)->get();

        if (!$database_results) {
            return null;
        }

        $pages = $this->listRecordsByKey($database_results);
        if ($regular_filters) {
            return parent::findBy($regular_filters, null, $returnSingle, $pages);
        } else {
            return $returnSingle ? reset($pages) : $pages;
        }
    }

    /**
     * @param $key
     * @param null $value
     * @return mixed
     */
    private function makeFilters($key, $value = null)
    {
        if (is_array($key)) {
            $filters = [];
            foreach ($key as $k => $v) {
                $filters[] = [$k, $v];
            }
        } else {
            $filters = [$key, $value];
        }
        $database_filters = $regular_filters = [];
        foreach ($filters as $key => $value) {
            if (in_array($key, $this->searchable_fields)) {
                $database_filters[] = [$key, $value];
            } else {
                $regular_filters[] = [$key, $value];
            }
        }
        return [$database_filters, $regular_filters];
    }

    /**
     * Write page data to repository.
     * @param Page $page
     * @return Page
     */
    protected function write(Page $page)
    {
        $page->localeSlug();
        $this->db->table('pages')->updateOrInsert([
            'locale' => $page->getAttribute('locale'),
            'slug' => $page->getAttribute('slug')
        ], $this->toDbRecord($page));
    }

    /**
     * Scratch page data to repository.
     * @param string $localeSlug
     */
    protected function scratch($localeSlug)
    {
        list($locale, $slug) = explode('/', $localeSlug, 2);
        $this->db->table('pages')->where(['locale' => $locale, 'slug' => $slug])->delete();
    }

    /**
     * Convert page to a suitable database record.
     * @param Page $page
     * @return array
     */
    protected function toDbRecord(Page $page)
    {
        $attributes = $page->toArray();
        $record = [];
        $data = [];

        foreach ($attributes as $key => $value) {
            if (!in_array($key, $this->db_fields) || $key == 'data') {
                $data[$key] = $value;
            } else {
                $record[$key] = $value;
            }
        }
        $record['data'] = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        return $record;
    }

    /**
     * Covert database record to a page object.
     * @param $values
     * @return null|Page
     */
    protected function fromDbRecord($values)
    {
        if (!$values) {
            return null;
        }

        $attributes = (array)$values;
        unset($attributes['id']);
        if (isset($attributes['created_at'])) {
            $attributes['created_at'] = (int)$attributes['created_at'];
        }
        if (isset($attributes['updated_at'])) {
            $attributes['updated_at'] = (int)$attributes['updated_at'];
        }

        $data = json_decode($attributes['data'], true);
        unset($attributes['data']);
        foreach ($data as $k => $v) {
            $attributes[$k] = $v;
        }

        foreach ($attributes as $k => $v) {
            if (is_null($v)) {
                unset($attributes[$k]);
            }
        }

        return new Page($attributes);
    }

    /**
     * Return page list keyed with localeSlug.
     * @param array $records
     * @return array
     */
    private function listRecordsByKey($records)
    {
        $result = [];
        foreach ($records as $record) {
            $page = $this->fromDbRecord($record);
            $result[$page->localeSlug()] = $page;
        }
        return $result;
    }
}