<?php

namespace ShvetsGroup\JetPages\Page;

use Illuminate\Support\Str;

trait DynamicFields
{
    public $data = [];

    private $contentAttributes = null;

    public function getAttribute($key)
    {
        // ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        if (!$key) {
            return;
        }
        // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

        if (isset($this->slowReadAttributes[$key])) {

            // ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
            // If the attribute exists in the attribute array or has a "get" mutator we will
            // get the attribute's value. Otherwise, we will proceed as if the developers
            // are asking for a relationship's value. This covers both types of values.
            if (array_key_exists($key, $this->getAttributes(true)) ||
                $this->hasGetMutator($key) ||
                $this->isClassCastable($key)) {
                return $this->getAttributeValue($key);
            }

            // Here we will determine if the model base class itself contains this given key
            // since we don't want to treat any of those methods as relationships because
            // they are all intended as helper methods and none of these are relations.
            if (method_exists(self::class, $key)) {
                return;
            }
            // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

        } else {
            if (isset($this->attributes[$key])) {
                return $this->attributes[$key];
            }
        }

        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        // ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        return $this->getRelationValue($key);
        // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
    }

    public function setAttribute($key, $value)
    {
        if (isset($this->slowWriteAttributes[$key])) {

            // ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
            // First we will check for the presence of a mutator for the set operation
            // which simply lets the developers tweak the attribute as it is set on
            // the model, such as "json_encoding" an listing of data for storage.
            if ($this->hasSetMutator($key)) {
                return $this->setMutatedAttributeValue($key, $value);
            }

            // If an attribute is listed as a "date", we'll convert it from a DateTime
            // instance into a form proper for storage on the database tables using
            // the connection grammar's date format. We will auto set the values.
            elseif ($value && $this->isDateAttribute($key)) {
                $value = $this->fromDateTime($value);
            }

            if ($this->isClassCastable($key)) {
                $this->setClassCastableAttribute($key, $value);

                return $this;
            }

            if ($this->isJsonCastable($key) && !is_null($value)) {
                $value = $this->castAttributeAsJson($key, $value);
            }

            // If this attribute contains a JSON ->, we'll set the proper value in the
            // attribute's underlying array. This takes care of properly nesting an
            // attribute in the array's value in the case of deeply nested items.
            if (Str::contains($key, '->')) {
                return $this->fillJsonAttribute($key, $value);
            }
            // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
        }

        if (!array_key_exists($key, $this->attributes)) {
            // Reset content attributes cache if adding a new field.
            if (!isset($this->data[$key])) {
                $this->contentAttributes = null;
            }

            $this->data[$key] = $value;
        } else {
            $this->attributes[$key] = $value;
        }

        // ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
        return $this;
        // ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
    }

    /**
     * Add data to the list of attributes (for proper saving).
     */
    public function getAttributes($fast = false)
    {
        $attributes = parent::getAttributes();

        if (!$fast) {
            $attributes['data'] = json_encode($this->data);
        }

        return $attributes;
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        $attributes = $attributes + $this->data;

        unset($attributes['data']);

        return $attributes;
    }

    /**
     * Set the array of model attributes. No checking is done.
     *
     * @param  array  $attributes
     * @param  bool  $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        parent::setRawAttributes($attributes, $sync);

        if (isset($attributes['data'])) {
            $this->data = json_decode($attributes['data'], true);
        }

        return $this;
    }

    /**
     * Return array of content attribute names (content_*).
     * @return array
     */
    public function getContentAttributes()
    {
        if ($this->contentAttributes === null) {
            $contentAttributes = array_filter(array_keys($this->data), function ($key) {
                return Str::startsWith($key, 'content_');
            });
            $contentAttributes = array_merge(['content'], $contentAttributes);
            $this->contentAttributes = $contentAttributes;
        }
        return $this->contentAttributes;
    }
}