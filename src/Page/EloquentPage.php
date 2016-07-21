<?php namespace ShvetsGroup\JetPages\Page;

use Illuminate\Database\Eloquent\Model;

class EloquentPage extends Model implements Pageable
{
    use PageTrait;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pages';

    /**
     * Attribute casting.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

    protected $known_fields = ['id', 'slug', 'locale', 'title', 'data'];

    /**
     * Remove a key from attributes.
     *
     * @param $key
     */
    public function removeAttribute($key)
    {
        if (!isset($this->{$key})) {
            return;
        }
        unset($this->attributes[$key]);
    }

    /**
     * Find a page by string uri.
     *
     * @param $uri
     * @return null|EloquentPage
     */
    public function findByUri($uri) {
        $slug = $this->uriToSlug($uri);
        $page = $this->where('slug', $slug)->first();
        return $page ?: null;
    }

    /**
     * Helper to get data attribute sub keys.
     *
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function getData($key, $default = null)
    {
        return array_get($this->data, $key, $default);
    }

    /**
     * Helper to set data attribute sub-keys.
     *
     * @param $key
     * @param $value
     */
    public function setData($key, $value)
    {
        $data = $this->data;
        array_set($data, $key, $value);
        $this->data = $data;
    }

    /**
     * Data, which does not fit into database columns should be stored in "data" column as json.
     */
    public function save(array $options = []) {
        $attributes = $this->getAttributes();
        $new_attributes = $new_data = [];
        foreach ($attributes as $key => $attr) {
            if (in_array($key, $this->known_fields)) {
                $new_attributes[$key] = $attr;
            }
            else {
                $new_data[$key] = $attr;
            }
        }
        $new_attributes['data'] = $this->asJson($new_data);

        $this->setRawAttributes($new_attributes);
        parent::save($options);
        $this->setRawAttributes($attributes);
    }

    /**
     * On filling page with data, we should also populate attributes with values from "data".
     */
    public function fill(array $attributes) {
        parent::fill($attributes);
        if (isset($attributes['data'])) {
            parent::fill($attributes['data']);
        }
    }

    /**
     * Get the time of last page update.
     * @return int
     */
    public function lastUpdatedTime()
    {
        $last_updated = static::orderBy('updated_at', 'DESC')->first();
        return $last_updated ? $last_updated->updated_at->timestamp : 0;
    }
}
