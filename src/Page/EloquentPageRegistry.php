<?php

namespace ShvetsGroup\JetPages\Page;

class EloquentPageRegistry extends SimplePageRegistry
{
    /**
     * @var \Illuminate\Database\Connection
     */
    private $db;
    protected $db_fields = ['id', 'locale', 'slug', 'title', 'data', 'created_at', 'updated_at'];
    protected $searchable_fields = ['locale', 'slug', 'title', 'created_at', 'updated_at'];

    public function __construct(array $pages = [])
    {
        parent::__construct($pages);
        $this->db = app('Illuminate\Database\Connection');
    }

    /**
     * Clear all generated content.
     */
    public function reset()
    {
        parent::reset();
        $this->db->table('pages')->truncate();
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

        $this->index = [];
        foreach ($this->db->table('pages')->get(['locale', 'slug', 'updated_at']) as $record) {
            $localeSlug = PageUtils::makeLocaleSlug($record->locale, $record->slug);
            $this->index[$localeSlug] = $record->updated_at;
        }
        return $this->index;
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

        $pages = $this->db->table('pages')->get();
        $pages = $this->listRecordsByKey($pages);
        $this->addAll($pages);
        return $pages;
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

        $data = $this->db->table('pages')->where('locale', $locale)->where('slug', $slug)->first();
        $page = $data ? $this->fromDbRecord($data) : null;
        return $page;
    }

    /**
     * Get the time of last page update.
     * @return string
     */
    public function lastUpdatedTime()
    {
        $last_updated = $this->db->table('pages')->orderBy('updated_at', 'DESC')->first();
        return $last_updated ? $last_updated->updated_at : 0;
    }

    /**
     * Write page data to repository.
     * @param Page $page
     * @return Page
     */
    public function save(Page $page)
    {
        parent::save($page);

        $page->localeSlug();
        $this->db->table('pages')->updateOrInsert([
            'locale' => $page->getAttribute('locale'),
            'slug' => $page->getAttribute('slug')
        ], $this->toDbRecord($page));
        return $page;
    }

    /**
     * Scratch page data to repository.
     * @param string $localeSlug
     */
    protected function scratch($localeSlug)
    {
        parent::scratch($localeSlug);

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
            $attributes['created_at'] = $attributes['created_at'];
        }
        if (isset($attributes['updated_at'])) {
            $attributes['updated_at'] = $attributes['updated_at'];
        }

        $data = json_decode($attributes['data'], true);
        unset($attributes['data']);
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $attributes[$k] = $v;
            }
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