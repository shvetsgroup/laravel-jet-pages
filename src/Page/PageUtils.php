<?php

namespace ShvetsGroup\JetPages\Page;

class PageUtils
{
    /**
     * Sanitize uri for usage as slug.
     *
     * @param $uri
     * @return string
     */
    static function uriToSlug($uri)
    {
        return in_array($uri, ['', '/']) ? 'index' : $uri;
    }

    /**
     * Sanitize uri for usage as slug.
     *
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
     * @param $url
     * @return array
     */
    static function extractLocaleFromUrl($url)
    {
        $parts = parse_url($url);

        $localeDomains = config('laravellocalization.localeDomains');
        if ($localeDomains) {
            $domain = $parts['host'];
            $localesOnThisDomain = array_wrap(array_get($localeDomains, $domain, array_get($localeDomains, '')));

            if (!$localesOnThisDomain) {
                throw new \Exception("Can not determine locale configuration on this domain.");
            }

            return static::extractLocale($parts['path'] ?? '', false, reset($localesOnThisDomain), array_combine($localesOnThisDomain, $localesOnThisDomain));
        }

        return static::extractLocale($parts['path'] ?? '');
    }

    /**
     * Extract locale from uri.
     *
     * @param $uri
     * @return array
     */
    static function extractLocaleFromUri($uri)
    {
        return static::extractLocale($uri);
    }

    /**
     * Extract locale from localeSlug.
     *
     * @param $uri
     * @return array
     */
    static function extractLocaleFromLocaleSlug($localeSlug)
    {
        return static::extractLocale($localeSlug, true);
    }

    private static function extractLocale($uri, $uriIsLocaleSlug = false, $defaultLocale = null, $supportedLocales = null)
    {
        $defaultLocale = $defaultLocale ?: config('app.default_locale', '');
        $hideDefaultLocaleInUrl = config('laravellocalization.hideDefaultLocaleInURL', true);

        if ($uriIsLocaleSlug) {
            $hideDefaultLocaleInUrl = false;
        }

        $uri = ltrim($uri, "/ \t\n\r\0\x0B");
        $uriHasParts = mb_strpos($uri, '/') !== false;

        if ($uriHasParts) {
            list($locale, $path) = explode('/', $uri, 2);
        } else {
            $locale = $uri;
            $path = '';
        }

        if (static::isValidLocale($locale, $supportedLocales)) {
            if ($locale === $defaultLocale && $hideDefaultLocaleInUrl) {
                return [$defaultLocale, $uri];
            } else {
                return [$locale, $path];
            }
        } else {
            return [$defaultLocale, $uri];
        }
    }

    /**
     * Check if the passed value is valid locale.
     *
     * @param $string
     * @param null $supportedLocales
     * @return bool
     */
    static function isValidLocale($string, $supportedLocales = null)
    {
        if (mb_strlen($string) != 2) {
            return false;
        }

        $locales = $supportedLocales ?:
            config('laravellocalization.supportedLocales') ?:
                config('jetpages.supportedLocales', [config('app.default_locale') => []]);
        if (!isset($locales[$string])) {
            return false;
        }

        return true;
    }

    /**
     * Make a locale/slug combination string.
     * This should be the same for [locale => 'en', 'slug' => 'test'] and [locale => '', 'slug' => 'en/test']
     *
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
     *
     * @param $locale
     * @param $slug
     * @return string
     */
    static function makeUri($locale, $slug)
    {
        $prefix = static::getLocalePrefix($locale);
        $uri = static::slugToUri($slug);

        if ($prefix && $uri == '/') {
            return $locale;
        }

        return $prefix . $uri;
    }

    /**
     * Get urls prefix for a given locale ("ru/" or '').
     *
     * @param $locale
     * @return string
     */
    static function getLocalePrefix($locale)
    {
        if (!$locale) {
            return '';
        }

        $hideDefaultLocaleInUrl = config('laravellocalization.hideDefaultLocaleInURL', true);
        if (!$hideDefaultLocaleInUrl) {
            return $locale . '/';
        }

        $localeDomains = config('laravellocalization.localeDomains');
        if (!$localeDomains) {
            return ($locale == config('app.default_locale', '')) ? '' : $locale . '/';
        }

        $domain = static::getHost();
        foreach ($localeDomains as $d => $localesInDomain) {
            $localesInDomain = array_wrap($localesInDomain);
            if (in_array($locale, $localesInDomain)) {
                $domain = $d;
            }
        }
        $localesOnThisDomain = array_wrap(array_get($localeDomains, $domain));

        if ($locale == reset($localesOnThisDomain)) {
            return '';
        }

        return $locale . '/';
    }

    /**
     * Sanitize uri for usage as slug.
     *
     * @param $uri
     * @return string
     */
    static function uriToLocaleSlug($uri)
    {
        list($locale, $uri) = static::extractLocaleFromURI($uri);
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
        list($locale, $uri) = static::extractLocaleFromURI($uri);
        $slug = static::uriToSlug($uri);
        return [$locale, $slug];
    }

    /**
     * Get a domain name suited for this locale;
     * @param $locale
     * @return string
     */
    static function getLocaleDomain($locale)
    {
        $currentDomain = static::getHost();

        $localeDomains = config('laravellocalization.localeDomains');
        if (is_array($localeDomains)) {

            foreach ($localeDomains as $domain => $localesInDomain) {
                $localesInDomain = array_wrap($localesInDomain);

                if (in_array($locale, $localesInDomain)) {

                    if ($domain === '' && isset($localeDomains[$currentDomain])) {
                        $domain = static::getHost(config('app.url'));
                    }

                    return $domain;
                }
            }
        }

        return $currentDomain;
    }

    /**
     * Get the current host. The reason why we use this instead of request()->getHost()
     * is because we want to be able to mock the host in unit tests.
     * @return mixed
     */
    static function getHost($url = null)
    {
        if (!$url) {
            $url = url()->current();
        }

        return parse_url($url)['host'];
    }

    /**
     * Get the current baseURL. The reason why we use this instead of request()->getHost()
     * is because we want to be able to mock the host in unit tests.
     * @return mixed
     */
    static function getBaseUrl($url = null)
    {
        if (!$url) {
            $url = url()->current();
        }

        $parts = parse_url($url);
        return $parts['scheme'] . '://' . $parts['host'] . '/';
    }

    /**
     * Get an URL with a proper local domain.
     *
     * @param $uri
     * @param null $locale
     * @return string
     */
    static function absoluteUrl($uri, $locale = null) {
        $url = url($uri);

        $localeDomains = config('laravellocalization.localeDomains');
        if ($localeDomains) {

            $locale = $locale ?? app()->getLocale();

            $domain = static::getLocaleDomain($locale);

            if ($domain) {
                $url = preg_replace('#(?<=://)([^/]+?)(?=(/|\?|$))#', $domain, $url);
            }
        }

        return $url;
    }
}