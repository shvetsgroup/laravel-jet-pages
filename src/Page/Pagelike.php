<?php namespace ShvetsGroup\JetPages\Page;

use Illuminate\Contracts\Support\Arrayable;

interface Pagelike extends Arrayable
{
    public function fill(array $attributes);

    public function save(array $options = []);

    public function getAttribute($key);

    public function setAttribute($key, $value);

    public function removeAttribute($key);

    /**
     * @param $uri
     * @return Pagelike
     */
    public function findByUri($uri);

    /**
     * @param $uri
     * @return Pagelike
     */
    public function findByUriOrFail($uri);

    /**
     * @return int
     */
    public function lastUpdatedTime();
}