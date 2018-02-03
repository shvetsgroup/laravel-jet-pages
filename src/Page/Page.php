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
    function localeSlug($slugField = 'slug')
    {
        // We cache the value during page object life time.
        if ($slugField == 'slug' && $localeSlug = $this->getAttribute('localeSlug')) {
            return $localeSlug;
        }

        if (!isset($this->attributes[$slugField])) {
            if ($slugField == 'slug') {
                throw new PageException("Page requires a slug field.");
            } else {
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
     * @param null $default
     * @return mixed
     */
    public function getAttribute($key, $default = null)
    {
        return array_get($this->attributes, $key, $default);
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
    static function uriToSlug($uri)
    {
        return in_array($uri, ['', '/']) ? 'index' : $uri;
    }

    /**
     * Sanitize uri for usage as slug.
     * @param $slug
     * @return string
     */
    static function slugToUri($slug)
    {
        return $slug == 'index' ? '/' : $slug;
    }

    /**
     * Extract locale from uri.
     *
     * @param $uri
     * @param null $localeInUrl
     * @return array
     */
    static function extractLocale($uri, $localeInUrl = null)
    {
        $defaultLocale = config('app.default_locale', '');
        $defaultLocaleIsInUrl = $localeInUrl === null ? config('jetpages.default_locale_in_url', false) : $localeInUrl;
        $uri_has_parts = strpos($uri, '/') !== false;

        if ($uri_has_parts) {
            list($locale, $path) = explode('/', $uri, 2);
            if (static::isValidLocale($locale)) {
                if ($locale == $defaultLocale && $defaultLocaleIsInUrl) {
                    return [$defaultLocale, $uri];
                } else {
                    return [$locale, $path];
                }
            } else {
                return [$defaultLocale, $uri];
            }
        } else {
            if (static::isValidLocale($uri)) {
                if ($uri == $defaultLocale && $defaultLocaleIsInUrl) {
                    return [$defaultLocale, $uri];
                } else {
                    return [$uri, ''];
                }
            } else {
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
    static function isValidLocale($string)
    {
        if (strlen($string) != 2) {
            return false;
        }

        $locales = config('laravellocalization.supportedLocales') ?: config('jetpages.supportedLocales', []);
        if (!isset($locales[$string])) {
            return false;
        }

        return true;
    }

    /**
     * Make a locale/slug combination string.
     * This should be the same for [locale => 'en', 'slug' => 'test'] and [locale => '', 'slug' => 'en/test']
     * @param $locale
     * @param $slug
     * @return string
     */
    static function makeLocaleSlug($locale, $slug)
    {
        return ($locale ? $locale . '/' : '') . $slug;
    }

    /**
     * Generate valid uri from locale and slug.
     * @param $locale
     * @param $slug
     * @return string
     */
    static function makeLocaleUri($locale, $slug)
    {
        $uri = static::slugToUri($slug);

        $locale_prefix = (!$locale || $locale == config('app.default_locale', '')) ? '' : $locale . '/';

        if ($locale_prefix && $uri == '/') {
            return $locale;
        } else {
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
     * @param bool $absolute
     * @param bool $withoutDomain
     * @return string
     */
    function uri($absolute = false, $withoutDomain = false)
    {
        $uri = $this->getAttribute('uri');
        if (!$uri) {
            $locale = $this->getAttribute('locale');
            $slug = $this->getAttribute('slug');
            $uri = static::makeLocaleUri($locale, $slug);
        }
        if ($absolute) {
            $url = url($uri);
            if ($withoutDomain) {
                $parsed = parse_url($url);
                $url = isset($parsed['path']) ? $parsed['path'] : '/';
            }
            return $url;
        } else {
            return $uri;
        }
    }

    /**
     * Get the translation uris for a page.
     * @param bool $absolute
     * @return array
     */
    function translationUris($absolute = false)
    {
        $locales = config('laravellocalization.supportedLocales') ?: config('jetpages.supportedLocales', []);
        if (!is_array($locales) || count($locales) < 2) {
            return [];
        }

        $locale_uris = [];
        $pages = app('pages');
        $this_locale = $this->getAttribute('locale');
        $this_slug = $this->getAttribute('slug');
        foreach ($locales as $locale => $data) {
            if ($locale == $this_locale) {
                continue;
            }
            if ($page = $pages->findBySlug($locale, $this_slug)) {
                $locale_uris[$locale] = $page->uri($absolute);
            }
        }

        return $locale_uris;
    }

    /**
     * Return all alternative uris of a page.
     *
     * @param bool $absolute
     * @return array
     */
    function alternativeUris($absolute = false)
    {
        $locale = $this->getAttribute('locale');
        $result = array_merge([$locale => $this->uri($absolute)], $this->translationUris($absolute));
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
     * Convert page to array.
     * @return array
     */
    public function toArray()
    {
        $result = $this->attributes ?: [];
        $result['uri'] = $this->uri();
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
        $result['alternativeUris'] = $this->alternativeUris();
        $result['href'] = $this->uri(true, true);
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
            $v = $view_provider ? $view_provider . '::' . $view : $view;
            if (view()->exists($v)) {
                $view = $v;
                break;
            }
        }
        return $view;
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