<?php namespace ShvetsGroup\JetPages\Page;

use Illuminate\Contracts\Support\Arrayable;

interface Pageable extends Arrayable {
    public function fill(array $attributes);
    public function save(array $options = []);
    public function getAttribute($key);
    public function setAttribute($key, $value);
    public function removeAttribute($key);
    public function findByUri($uri);
    public function findByUriOrFail($uri);
    public function lastUpdatedTime();
}