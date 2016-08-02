<?php namespace ShvetsGroup\JetPages\Page;

trait PageTrait
{
    /**
     * Make sure slug is correct.
     * @param string $slugAttribute
     * @param bool $required
     * @return null|string
     * @throws PageException
     */
    protected function checkSlug($slugAttribute = 'slug', $required = true)
    {
        $slug = $this->getAttribute($slugAttribute);

        if (!$slug) {
            if ($required) {
                throw new PageException("Page requires a slug field.");
            }
            else {
                return null;
            }
        }

        $slug = $this->uriToSlug($slug);
        $this->setAttribute($slugAttribute, $slug, true);
        return $slug;
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
     * Sanitize uri for usage as slug.
     *
     * @param $uri
     * @return string
     */
    function uriToLocaleSlug($uri)
    {
        list($locale, $uri) = $this->extractLocale($uri);
        $slug = $this->uriToSlug($uri);
        return $this->makeLocaleSlug($locale, $slug);
    }

    /**
     * Extract locale from a slug.
     * @param $slug
     * @return array
     */
    static function extractLocale($slug) {
        if (strpos($slug, '/') !== false) {
            list($locale, $slug) = explode('/', $slug, 2);
        }
        else {
            $locale = '';
        }
        return [$locale, $slug];
    }

    /**
     * Sanitize uri for usage as slug.
     *
     * @param $uri
     * @return array
     */
    function uriToLocaleSlugArray($uri)
    {
        list($locale, $uri) = $this->extractLocale($uri);
        $slug = $this->uriToSlug($uri);
        return [$locale, $slug];
    }

    /**
     * Return page's locale/slug combination string.
     * @return string
     */
    function localeSlug() {
        $locale = $this->getAttribute('locale');
        $slug = $this->getAttribute('slug');
        return $this->makeLocaleSlug($locale, $slug);
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

        $locale_prefix = (!$locale || $locale == config('app.locale')) ? '' : $locale . '/';
        
        if ($locale_prefix && $uri == '/') {
            return $locale;
        }
        else {
            return $locale_prefix . $slug;
        }
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
        $result['uri'] = $this->uri();
        return $result;
    }
}