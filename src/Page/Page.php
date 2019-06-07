<?php

namespace ShvetsGroup\JetPages\Page;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;
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
        if (!isset($attributes['private'])) {
            $attributes['private'] = false;
        }
        $this->setAttributes($attributes, true);
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
        $localeSlug = PageUtils::makeLocaleSlug($locale, $slug);

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
        return Arr::get($this->attributes, $key, $default);
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
            $value = PageUtils::uriToSlug($value);
            if (!$force) {
                Arr::set($this->attributes, 'oldSlug', array_get($this->attributes, 'slug'));
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
            $uri = PageUtils::makeUri($locale, $slug);
        }

        if (!$absolute) {
            return $uri;
        }

        $url = PageUtils::absoluteUrl($uri, $this->locale);

        if ($withoutDomain) {
            $parsed = parse_url($url);
            $url = isset($parsed['path']) ? $parsed['path'] : '/';
        }

        return $url;
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
    public function render($reset = false)
    {
        return view($this->getRenderableView(), $this->renderArray())->render();
    }

    /**
     * Dynamically retrieve attributes on the page.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the page.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute or relation exists on the page.
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->getAttribute($key));
    }

    /**
     * Unset an attribute on the page.
     *
     * @param string $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
}