<?php namespace ShvetsGroup\JetPages\Page;

use Illuminate\Database\Eloquent\Model;

class EloquentPage extends Model implements Page
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
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    protected $known_fields = ['id', 'locale', 'slug', 'title', 'data', 'created_at', 'updated_at'];

    /**
     * Remove a key from attributes.
     *
     * @param $key
     * @return $this
     */
    public function removeAttribute($key)
    {
        if (isset($this->{$key})) {
            unset($this->attributes[$key]);
        }
        return $this;
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
        return array_get($this->getAttribute('data'), $key, $default);
    }

    /**
     * Helper to set data attribute sub-keys.
     *
     * @param $key
     * @param $value
     */
    public function setData($key, $value)
    {
        $data = $this->getAttribute('data');
        array_set($data, $key, $value);
        $this->setAttribute('data', $data);
    }

    /**
     * Data, which does not fit into database columns should be stored in "data" column as json.
     *
     * @param array $options
     * @return $this
     */
    public function save(array $options = [])
    {
        $locale = $this->getAttribute('locale') ?: '';
        $slug = $this->checkSlug();

        // Check if slug exists, because it might have changed.
        if (!$this->exists) {
            if ($existing = static::where('locale', $locale)->where('slug', $slug)->first()) {
                $this->exists = true;
                $key = $this->getKeyName();
                $this->setAttribute($key, $existing->getAttribute($key));
            }
        }

        $attributes = $this->getAttributes();
        $new_attributes = $new_data = [];
        foreach ($attributes as $key => $attr) {
            if (in_array($key, $this->known_fields)) {
                $new_attributes[$key] = $attr;
            } else {
                $new_data[$key] = $attr;
            }
        }
        $new_attributes['data'] = $this->asJson($new_data);

        $this->attributes = $new_attributes;

        parent::save($options);

        $added_attributes = array_diff($this->attributes, $new_attributes);
        $this->attributes = array_merge($attributes, $added_attributes);

        return $this;
    }

    /**
     * On filling page with data, we should also populate attributes with values from "data".
     *
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        parent::fill($attributes);
        if (isset($attributes['data'])) {
            parent::fill($attributes['data']);
        }
        return $this;
    }

    /**
     * On filling page with data, we should also populate attributes with values from "data".
     *
     * @param array $attributes
     * @param bool $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        if (isset($attributes['data'])) {
            $data = is_string($attributes['data']) ? json_decode($attributes['data'], true) : $attributes['data'];
            unset($attributes['data']);
            $attributes = array_merge($attributes, $data);
        }
        parent::setRawAttributes($attributes, $sync);
        return $this;
    }

    /**
     * Make any page field fillable, since they will translate to data array.
     *
     * @param string $key
     * @return true
     */
    public function isFillable($key)
    {
        return true;
    }

    /**
     * Remove from the model id and data fields so that it would be compatible with Page output.
     *
     * @return array
     */
    public function toArray()
    {
        $result = parent::toArray();
        unset($result['id']);
        unset($result['data']);
        if (isset($result['title']) && !$result['title']) {
            unset($result['title']);
        }
        if (isset($result['locale']) && !$result['locale']) {
            unset($result['locale']);
        }
        $result['uri'] = $this->uri();
        return $result;
    }
}
