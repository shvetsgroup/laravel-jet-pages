<?php

namespace ShvetsGroup\JetPages\Page;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * User model.
 *
 * @method static findBySlug(string $locale, string $slug): ?Page
 * @method static findByLocaleTitle(string $locale, string $title): ?Page
 * @method static chunk(integer $count, callable $callback)
 * @method static where($key, $value): PageQuery
 * @method static get(): PageCollection
 * @method static first(): ?Page
 */
class PageQuery
{
    protected $query;

    public function __construct()
    {
    }

    public function getQuery()
    {
        if (!$this->query) {
            $this->query = DB::table('pages');
        }
        return $this->query;
    }

    public function _get(): PageCollection
    {
        $records = $this->getQuery()->get();
        $pages = PageCollection::fromRecords($records);
        return $pages;
    }

    public function _first(): ?Page
    {
        $record = $this->getQuery()->first();
        if ($record) {
            $page = new Page((array) $record);
            $page->exists = true;
            return $page;
        }
        return null;
    }

    public function _chunk(integer $count, callable $callback)
    {
        $query = $this->getQuery();
        $query->orderBy('id', 'asc');
        return $query->chunk($count, function ($pageRecords) use ($callback) {
            $pages = PageCollection::fromRecords($pageRecords);
            call_user_func($callback, $pages);
        });
    }

    /**
     * Load a page by locale and slug.
     */
    public function _findBySlug(string $locale, string $slug): ?Page
    {
        $this->query = $this->getQuery()->where(['locale' => $locale, 'slug' => $slug]);
        return $this->_first();
    }

    /**
     * Load a page by locale and title.
     */
    public function _findByLocaleTitle(string $locale, string $title): ?Page
    {
        $this->query = $this->getQuery()->where(['locale' => $locale, 'title' => $title]);
        return $this->_first();
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this, '_'.$name)) {
            return call_user_func_array([$this, '_'.$name], $arguments);
        }

        $query = $this->getQuery();
        $result = call_user_func_array([$query, $name], $arguments);

        if ($result instanceof Builder) {
            $this->query = $result;
            return $this;
        } else {
            return $result;
        }
    }

    public static function __callStatic($name, $arguments)
    {
        $obj = new static();
        return call_user_func_array([$obj, $name], $arguments);
    }
}