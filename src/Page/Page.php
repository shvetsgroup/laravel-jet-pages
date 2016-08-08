<?php namespace ShvetsGroup\JetPages\Page;

use Illuminate\Contracts\Support\Arrayable;

class Page implements Arrayable
{
    /**
     * @var array
     */
    private $attributes;

    public function __construct(array $attributes = [])
    {
        if (!isset($attributes['locale'])) {
            $attributes['locale'] = config('app.default_locale', '');
        }
        $this->setAttributes($attributes, true);
    }

    /**
     * Return page's locale/slug combination string.
     * @param string $slugField
     * @return string
     * @throws PageException
     */
    function localeSlug($slugField = 'slug') {
        // We cache the value during page object life time.
        if ($slugField == 'slug' && $localeSlug = $this->getAttribute('localeSlug')) {
            return $localeSlug;
        }

        if (!isset($this->attributes[$slugField])) {
            if ($slugField == 'slug') {
                throw new PageException("Page requires a slug field.");
            }
            else {
                return null;
            }
        }

        $locale = $this->getAttribute('locale');
        $slug = $this->getAttribute($slugField);
        $localeSlug = $this->makeLocaleSlug($locale, $slug);

        if ($slugField == 'slug') {
            $this->setAttribute('localeSlug', $localeSlug);
        }

        return $localeSlug;
    }

    /**
     * Convert page to array.
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes = [], $force = false)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value, $force);
        }
        return $this;
    }

    /**
     * Helper to get attribute.
     *
     * @param $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        return array_get($this->attributes, $key);
    }

    /**
     * Helper to set attribute.
     *
     * @param string $key
     * @param mixed $value
     * @param bool $force
     * @return $this
     */
    public function setAttribute($key, $value, $force = false)
    {
        if (($key == 'created_at' || $key == 'updated_at') && is_object($value)) {
            $value = $value->timestamp;
        }
        if ($key == 'slug') {
            $value = static::uriToSlug($value);
            if (!$force) {
                array_set($this->attributes, 'oldSlug', array_get($this->attributes, 'slug'));
            }
        }
        if ($key == 'slug' || $key == 'locale') {
            unset($this->attributes['localeSlug']);
        }
        array_set($this->attributes, $key, $value);
        return $this;
    }

    /**
     * Remove a key from attributes.
     *
     * @param $key
     * @return $this
     */
    public function removeAttribute($key)
    {
        if (isset($this->attributes[$key])) {
            unset($this->attributes[$key]);
        }
        return $this;
    }

    /**
     * Fill the page with an array of attributes.
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    /**
     * Sanitize uri for usage as slug.
     * @param $uri
     * @return string
     */
    static function uriToSlug($uri) {
        return in_array($uri, ['', '/']) ? 'index' : $uri;
    }

    /**
     * Sanitize uri for usage as slug.
     * @param $slug
     * @return string
     */
    static function slugToUri($slug) {
        return $slug == 'index' ? '/' : $slug;
    }

    /**
     * Extract locale from uri.
     *
     * @param $uri
     * @param null $localeInUrl
     * @return array
     */
    static function extractLocale($uri, $localeInUrl = null) {
        $defaultLocale = config('app.default_locale', '');
        $defaultLocaleIsInUrl = $localeInUrl === null ? config('jetpages.default_locale_in_url', true) : $localeInUrl;
        $uri_has_parts = strpos($uri, '/') !== false;

        if ($uri_has_parts) {
            list($locale, $path) = explode('/', $uri, 2);
            if (static::isValidLocale($locale)) {
                if ($locale == $defaultLocale && $defaultLocaleIsInUrl) {
                    return [$defaultLocale, $uri];
                }
                else {
                    return [$locale, $path];
                }
            }
            else {
                return [$defaultLocale, $uri];
            }
        }
        else {
            if (static::isValidLocale($uri)) {
                if ($uri == $defaultLocale && $defaultLocaleIsInUrl) {
                    return [$defaultLocale, $uri];
                }
                else {
                    return [$uri, ''];
                }
            }
            else {
                return [$defaultLocale, $uri];
            }
        }
    }

    /**
     * Check if the passed value is valid locale.
     *
     * @param $string
     * @return bool
     */
    static function isValidLocale($string) {
        return strlen($string) == 2;
    }

    /**
     * Make a locale/slug combination string.
     * This should be the same for [locale => 'en', 'slug' => 'test'] and [locale => '', 'slug' => 'en/test']
     * @param $locale
     * @param $slug
     * @return string
     */
    static function makeLocaleSlug($locale, $slug) {
        return ($locale ? $locale . '/' : '') . $slug;
    }

    /**
     * Generate valid uri from locale and slug.
     * @param $locale
     * @param $slug
     * @return string
     */
    static function makeLocaleUri($locale, $slug) {
        $uri = static::slugToUri($slug);

        $locale_prefix = (!$locale || $locale == config('app.default_locale', '')) ? '' : $locale . '/';

        if ($locale_prefix && $uri == '/') {
            return $locale;
        }
        else {
            return $locale_prefix . $uri;
        }
    }

    /**
     * Sanitize uri for usage as slug.
     *
     * @param $uri
     * @return string
     */
    static function uriToLocaleSlug($uri)
    {
        list($locale, $uri) = static::extractLocale($uri);
        $slug = static::uriToSlug($uri);
        return static::makeLocaleSlug($locale, $slug);
    }

    /**
     * Sanitize uri for usage as slug.
     *
     * @param $uri
     * @return array
     */
    static function uriToLocaleSlugArray($uri)
    {
        list($locale, $uri) = static::extractLocale($uri);
        $slug = static::uriToSlug($uri);
        return [$locale, $slug];
    }

    /**
     * Generate valid uri from locale and slug.
     * @return string
     */
    function uri() {
        $locale = $this->getAttribute('locale');
        $slug = $this->getAttribute('slug');
        return static::makeLocaleUri($locale, $slug);
    }

    /**
     * Convert page to array.
     * @return array
     */
    public function toArray()
    {
        $result = $this->attributes ?: [];
        unset($result['localeSlug']);
        return $result;
    }

    /**
     * Convert page to array, suitable for rendering in view.
     * @return array
     */
    public function renderArray()
    {
        $result = $this->toArray();
        $result['uri'] = $this->uri();

        $locales = config('laravellocalization.supportedLocales') ?: [config('app.default_locale') => []];
        $result['locale_uri'] = [];
        foreach ($locales as $locale => $data) {
            $result['uris'][$locale] = Page::makeLocaleUri($locale, $this->getAttribute('slug'));
        }
        return $result;
    }

    /**
     * Dynamically retrieve attributes on the page.
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the page.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute or relation exists on the page.
     *
     * @param  string $key
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->getAttribute($key));
    }

    /**
     * Unset an attribute on the page.
     *
     * @param  string $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
}