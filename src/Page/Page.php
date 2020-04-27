<?php

namespace ShvetsGroup\JetPages\Page;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ShvetsGroup\JetPages\Facades\PageUtils;

class Page implements Arrayable
{
    private $contentAttributes = null;

    protected $attributes = [];

    protected $defaults = [
        'id' => null,
        'type' => null,
        'locale' => 'en',
        'slug' => null,
        'localeSlug' => null,
        'uri' => null,
        'url' => null,
        'href' => null,
        'title' => null,
        'content' => null,
        'private' => false,
        'cache' => true,
        'scanner' => null,
        'path' => null,
        'hash' => null,
        'updated_at' => null,
    ];

    public $exists = false;

    public $hasUnpackedData = false;

    public $hasChangedSinceRehash = false;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $this->defaults;
        $this->fill($attributes);
    }

    /**
     * @param  array  $attributes
     * @return Page
     */
    public static function create($attributes = [])
    {
        $page = new Page($attributes);
        $page->save();
        return $page;
    }

    public function save()
    {
        $attributes = [
            'data' => [],
        ];

        if ($this->hasUnpackedData()) {
            $this->unpackData();
        }

        if (!isset($this->attributes['hash']) || $this->hasChangedSinceRehash) {
            $this->rehash();
        }

        foreach ($this->attributes as $key => $value) {
            if ($key == 'updated_at' && $value instanceof Carbon) {
                $value = $value->toJSON();
            }

            if (array_key_exists($key, $this->defaults)) {
                $attributes[$key] = $value;
            } else {
                $attributes['data'][$key] = $value;
            }
        }
        $attributes['data'] = json_encode($attributes['data']);

        if ($this->exists) {
            PageQuery::where('id', $this->id)->update($attributes);
        } else {
            $this->id = PageQuery::insertGetId($attributes);
            $this->exists = true;
        }
    }

    public function fresh()
    {
        return PageQuery::where('id', $this->id)->first();
    }

    public function delete()
    {
        return PageQuery::where('id', $this->id)->delete();
    }

    /**
     * Whether a page is accessible via web.
     * @return bool
     */
    function isPrivate(): bool
    {
        return $this->getAttribute('private', false);
    }

    /**
     * Whether a page is not accessible via web.
     * @return bool
     */
    function isPublic(): bool
    {
        return !$this->isPrivate();
    }

    /**
     * Convert page to array.
     * @param  array  $attributes
     * @return $this
     */
    public function fill(array $attributes = [])
    {
        if (isset($attributes['data'])) {
            $this->hasUnpackedData = true;
        }

        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }

        if (isset($attributes['locale']) || isset($attributes['slug'])) {
            if (!isset($attributes['localeSlug'])) {
                $this->updateLocaleSlugAttribute();
            }
            if (!isset($attributes['uri']) || !isset($attributes['url']) || !isset($attributes['href'])) {
                $this->updateUrlAttributes();
            }
        }

        return $this;
    }

    public function unpackData()
    {
        $data = $this->attributes['data'];
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        $this->attributes = array_merge($this->attributes, $data);
        unset($this->attributes['data']);
        $this->hasUnpackedData = false;
    }

    public function hasUnpackedData($key = null)
    {
        if (!$this->hasUnpackedData) {
            return false;
        }

        if (!isset($this->attributes['data'])) {
            return false;
        }

        if ($key !== null && array_key_exists($key, $this->defaults)) {
            return false;
        }

        return true;
    }

    /**
     * Helper to set attribute.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  bool  $force
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        $this->hasChangedSinceRehash = true;

        if ($this->hasUnpackedData($key)) {
            $this->unpackData();
        }

        if ($key == 'locale') {
            $this->setLocaleAttribute($value);
        }

        if ($key == 'slug') {
            $this->setSlugAttribute($value);
        }

        $this->contentAttributes = null;

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Helper to get attribute.
     *
     * @param $key
     * @param  null  $default
     * @return mixed
     */
    public function getAttribute($key, $default = null)
    {
        if ($this->hasUnpackedData($key)) {
            $this->unpackData();
        }

        if ($key === 'hash' && $this->hasChangedSinceRehash) {
            return $this->rehash();
        }

        if ($key === 'updated_at') {
            return new Carbon($this->attributes[$key]);
        }

        return $this->attributes[$key] ?? $default;
    }


    /**
     * Set page locale.
     * @param $locale
     */
    public function setLocaleAttribute($locale)
    {
        $this->attributes['locale'] = $locale;
        $this->updateLocaleSlugAttribute();
        $this->updateUrlAttributes();
    }

    /**
     * Set page slug.
     * @param $slug
     */
    public function setSlugAttribute($slug)
    {
        $this->attributes['slug'] = $slug;
        $this->updateLocaleSlugAttribute();
        $this->updateUrlAttributes();
    }

    /**
     * Update locale-slug value of the page.
     */
    function updateLocaleSlugAttribute()
    {
        $locale = $this->getAttribute('locale');
        $slug = $this->getAttribute('slug');
        $localeSlug = PageUtils::makeLocaleSlug($locale, $slug);
        $this->attributes['localeSlug'] = $localeSlug;
    }

    /**
     * Update locale-slug value of the page.
     */
    function updateUrlAttributes()
    {
        $locale = $this->getAttribute('locale');
        $slug = $this->getAttribute('slug');

        $uri = PageUtils::makeUri($locale, $slug);
        $this->attributes['uri'] = $uri;

        $url = PageUtils::absoluteUrl($uri, $locale);
        $this->attributes['url'] = $url;

        $parsed = parse_url($url);
        $href = isset($parsed['path']) ? $parsed['path'] : '/';
        $this->attributes['href'] = $href;
    }

    /**
     * Return all alternative uris of a page.
     *
     * @param  bool  $absolute
     * @return array
     */
    function alternativeUris($absolute = false)
    {
        $locale = $this->attributes['locale'];
        $href = $this->attributes[$absolute ? 'url' : 'uri'];
        $result = array_merge([$locale => $href], $this->translationUris($absolute));
        ksort($result);

        // Default language first, and rest sorted by alphabet.
        $default_locale = config('app.default_locale', '');

        if ($default_locale && isset($result[$default_locale])) {
            $d = $result[$default_locale];
            unset($result[$default_locale]);
            $result = array_merge([$default_locale => $d], $result);
        }

        return $result;
    }

    /**
     * Get the translation uris for a page.
     * @param  bool  $absolute
     * @return array
     */
    function translationUris($absolute = false)
    {
        $locales = config('laravellocalization.supportedLocales') ?: config('jetpages.supportedLocales', []);
        if (!is_array($locales) || count($locales) < 2) {
            return [];
        }

        $locale_uris = [];
        $this_locale = $this->attributes['locale'];
        $this_slug = $this->attributes['slug'];

        foreach ($locales as $locale => $data) {
            if ($locale == $this_locale) {
                continue;
            }
            if ($page = PageQuery::findBySlug($locale, $this_slug)) {
                $locale_uris[$locale] = $page->attributes[$absolute ? 'url' : 'uri'];
            }
        }

        return $locale_uris;
    }

    /**
     * Get page hash.
     * @return string
     */
    public function rehash()
    {
        $attributes = $this->toArray();
        unset($attributes['id']);
        unset($attributes['hash']);
        $this->setAttribute('hash', md5(json_encode($attributes)));
        $this->hasChangedSinceRehash = false;
    }

    /**
     * Convert page to array.
     * @return array
     */
    public function toArray()
    {
        if ($this->hasUnpackedData()) {
            $this->unpackData();
        }

        $result = $this->attributes ?: [];

        return $result;
    }

    /**
     * Convert page to array, suitable for rendering in view.
     * @return array
     */
    public function renderArray()
    {
        $result = $this->toArray();
        $result['alternativeUris'] = $this->alternativeUris();

        $unwantedFields = ['scanner', 'path', 'relative_path', 'extension', 'id', 'cache', 'private', 'updated_at'];
        Arr::forget($result, $unwantedFields);

        return $result;
    }

    /**
     * Fetch a view for rendering.
     *
     * @return string
     */
    public function getRenderableView()
    {
        $view = $this->getAttribute('view') ?: 'page';
        $view_providers = array_merge([''], config('jetpages.extra_view_providers', []), ["sg/jetpages"]);
        foreach ($view_providers as $view_provider) {
            $v = $view_provider ? $view_provider.'::'.$view : $view;
            if (view()->exists($v)) {
                $view = $v;
                break;
            }
        }
        return $view;
    }

    public function getTitleHrefArray($customTitle = null, $shortTitle = true)
    {
        if ($customTitle) {
            $title = $customTitle;
        } else {
            if ($shortTitle && $shortTitle = $this->getAttribute('title_short')) {
                $title = $shortTitle;
            } else {
                $title = $this->getAttribute('title');
            }
        }

        return [
            'title' => $title,
            'href' => $this->getAttribute('href'),
        ];
    }

    /**
     * Render page to HTML.
     *
     * @return string
     */
    public function render()
    {
        return view($this->getRenderableView(), $this->renderArray())->render();
    }

    /**
     * Return array of content attribute names (content_*).
     * @return array
     */
    public function getContentAttributes()
    {
        if ($this->contentAttributes === null) {
            $contentAttributes = array_filter(array_keys($this->attributes), function ($key) {
                return Str::startsWith($key, 'content_');
            });
            $contentAttributes = array_merge(['content'], $contentAttributes);
            $this->contentAttributes = $contentAttributes;
        }
        return $this->contentAttributes;
    }

    /**
     * Dynamically retrieve attributes on the page.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the page.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute or relation exists on the page.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->getAttribute($key));
    }

    /**
     * Unset an attribute on the page.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
}