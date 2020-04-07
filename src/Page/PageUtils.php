<?php

namespace ShvetsGroup\JetPages\Page;

use Exception;
use Illuminate\Support\Arr;

class PageUtils
{
    protected $configLocaleDomains;
    protected $configSSL;
    protected $configDefaultLocale;
    protected $configHideDefaultLocaleInURL;
    protected $configSupportedLocales;
    protected $configAppUrl;
    protected $cacheLocalePrefix;
    protected $cacheLocaleDomain;

    public function __construct()
    {
        $this->refreshCaches();
    }

    public function refreshCaches()
    {
        $this->configLocaleDomains = config('sg.localeDomains');
        $this->configSSL = config('sg.ssl');
        $this->configDefaultLocale = config('app.default_locale', 'en');
        $this->configHideDefaultLocaleInURL = config('laravellocalization.hideDefaultLocaleInURL', true);
        $this->configSupportedLocales = config('laravellocalization.supportedLocales');
        $this->configAppUrl = config('app.url');

        $this->cacheLocalePrefix = [];
        $this->cacheLocaleDomain = [];
    }

    /**
     * Sanitize uri for usage as slug.
     *
     * @param $uri
     * @return string
     */
    function uriToSlug($uri)
    {
        return in_array($uri, ['', '/']) ? 'index' : $uri;
    }

    /**
     * Sanitize uri for usage as slug.
     *
     * @param $slug
     * @return string
     */
    function slugToUri($slug)
    {
        return $slug == 'index' ? '/' : $slug;
    }

    /**
     * Extract locale from uri.
     *
     * @param $url
     * @return array
     */
    function extractLocaleFromUrl($url)
    {
        $parts = parse_url($url);

        if ($this->configLocaleDomains) {
            $localesOnThisDomain = $this->getLocalesOnDomain($url);
            return $this->extractLocale($parts['path'] ?? '', false, reset($localesOnThisDomain), array_combine($localesOnThisDomain, $localesOnThisDomain));
        }

        return $this->extractLocale($parts['path'] ?? '');
    }

    /**
     * Extract locale from uri.
     *
     * @param $uri
     * @return array
     */
    function extractLocaleFromUri($uri)
    {
        return $this->extractLocale($uri);
    }

    /**
     * Extract locale from localeSlug.
     *
     * @param $localeSlug
     * @return array
     */
    function extractLocaleFromLocaleSlug($localeSlug)
    {
        return $this->extractLocale($localeSlug, true);
    }

    private function extractLocale($uri, $uriIsLocaleSlug = false, $defaultLocale = null, $supportedLocales = null)
    {
        $defaultLocale = $defaultLocale ?: $this->configDefaultLocale;

        $uri = ltrim($uri, "/ \t\n\r\0\x0B");
        $uriHasParts = mb_strpos($uri, '/') !== false;

        if ($uriHasParts) {
            list($locale, $path) = explode('/', $uri, 2);
        } else {
            $locale = $uri;
            $path = '';
        }

        if ($this->isValidLocale($locale, $supportedLocales)) {
            if ($locale === $defaultLocale && $this->configHideDefaultLocaleInURL && !$uriIsLocaleSlug) {
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
     * @param  null  $supportedLocales
     * @return bool
     */
    function isValidLocale($string, $supportedLocales = null)
    {
        $strlen = mb_strlen($string);
        if ($strlen != 2 && $strlen != 5) {
            return false;
        }

        $locales = $supportedLocales ?:
            $this->configSupportedLocales ?:
                [$this->configDefaultLocale => []];

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
    function makeLocaleSlug($locale, $slug)
    {
        return ($locale ? $locale.'/' : '').$slug;
    }

    /**
     * Generate valid uri from locale and slug.
     *
     * @param $locale
     * @param $slug
     * @param  bool  $onMainDomain
     * @return string
     */
    function makeUri($locale, $slug, $onMainDomain = false)
    {
        $prefix = $this->getLocalePrefix($locale, $onMainDomain);
        $uri = $this->slugToUri($slug);

        if ($prefix && $uri == '/') {
            return $locale;
        }

        return $prefix.$uri;
    }

    /**
     * Get urls prefix for a given locale ("ru/" or '').
     *
     * @param $locale
     * @param  bool  $onMainDomain
     * @return string
     */
    function getLocalePrefix($locale, $onMainDomain = false)
    {
        if (!$locale) {
            return '';
        }

        if (isset($this->cacheLocalePrefix[$locale][$onMainDomain])) {
            return $this->cacheLocalePrefix[$locale][$onMainDomain];
        }

        $this->cacheLocalePrefix[$locale] = $this->cacheLocalePrefix[$locale] ?? [];
        $this->cacheLocalePrefix[$locale][$onMainDomain] = (function ($locale, $onMainDomain) {
            if (!$this->configHideDefaultLocaleInURL) {
                return $locale.'/';
            }

            if (!$this->configLocaleDomains || $onMainDomain) {
                return ($locale == $this->configDefaultLocale) ? '' : $locale.'/';
            }

            $localeDomain = $this->getLocaleDomain($locale);
            $localesOnThisDomain = $this->getLocalesOnDomain($localeDomain);

            if ($locale == reset($localesOnThisDomain)) {
                return '';
            }

            return $locale.'/';
        })($locale, $onMainDomain);

        return $this->cacheLocalePrefix[$locale][$onMainDomain];
    }

    /**
     * Sanitize uri for usage as slug.
     *
     * @param $uri
     * @return string
     */
    function uriToLocaleSlug($uri)
    {
        list($locale, $uri) = $this->extractLocaleFromURI($uri);
        $slug = $this->uriToSlug($uri);
        return $this->makeLocaleSlug($locale, $slug);
    }

    /**
     * Sanitize uri for usage as slug.
     *
     * @param $uri
     * @return array
     */
    function uriToLocaleSlugArray($uri)
    {
        list($locale, $uri) = $this->extractLocaleFromURI($uri);
        $slug = $this->uriToSlug($uri);
        return [$locale, $slug];
    }

    /**
     * Get a domain name suited for this locale;
     * @param $locale
     * @return string
     */
    function getLocaleDomain($locale)
    {
        if (isset($this->cacheLocaleDomain[$locale])) {
            return $this->cacheLocaleDomain[$locale];
        }

        $currentDomain = $this->getHost();

        if (is_array($this->configLocaleDomains)) {
            $domainsForGivenLocale = Arr::wrap(Arr::get($this->configLocaleDomains, $locale, []));
            if (empty($domainsForGivenLocale)) {
                throw new Exception("Can not determine domain configuration for the locale [$locale].");
            }

            if (in_array($currentDomain, $domainsForGivenLocale)) {
                $this->cacheLocaleDomain[$locale] = $currentDomain;
                return $currentDomain;
            }

            if (in_array('', $domainsForGivenLocale)) {
                $locales = $this->getLocalesOnDomain($currentDomain);
                if (in_array($locale, $locales)) {
                    $this->cacheLocaleDomain[$locale] = $currentDomain;
                    return $currentDomain;
                }
            }

            $firstDomainForGivenLocale = reset($domainsForGivenLocale);
            if ($firstDomainForGivenLocale) {
                $this->cacheLocaleDomain[$locale] = $firstDomainForGivenLocale;
                return $firstDomainForGivenLocale;
            } else {
                $defaultDomain = $this->getHost($this->configAppUrl);
                $this->cacheLocaleDomain[$locale] = $defaultDomain;
                return $defaultDomain;
            }
        }

        $this->cacheLocaleDomain[$locale] = $currentDomain;
        return $currentDomain;
    }

    /**
     * Return locales that should be present on this domain.
     *
     * @param  null  $domain
     * @return array
     */
    function getLocalesOnDomain($domain = null)
    {
        $domain = $this->getHost($domain);

        if ($this->configLocaleDomains) {
            $localesOnThisDomain = [];
            foreach ($this->configLocaleDomains as $locale => $domains) {
                $domains = Arr::wrap($domains);
                if (in_array($domain, $domains)) {
                    $localesOnThisDomain[] = $locale;
                }
            }
            if (empty($localesOnThisDomain)) {
                foreach ($this->configLocaleDomains as $locale => $domains) {
                    $domains = Arr::wrap($domains);
                    if (in_array('', $domains)) {
                        $localesOnThisDomain[] = $locale;
                    }
                }
            }
            if (empty($localesOnThisDomain)) {
                throw new Exception("Can not determine locale configuration on this domain [$domain].");
            }
            return $localesOnThisDomain;
        } else {
            return $this->configSupportedLocales ? array_keys($this->configSupportedLocales) : [app()->getLocale()];
        }
    }

    /**
     * Get the current host. The reason why we use this instead of request()->getHost()
     * is because we want to be able to mock the host in unit tests.
     * @param  null  $url
     * @return mixed
     */
    function getHost($url = null)
    {
        if (!$url) {
            $url = url()->current();
        } else {
            $url = trim($url);

            // If $url is a host.
            if (preg_match('#^(((?!-))(xn--|_{1})?[a-z0-9-]{0,61}[a-z0-9]{1}\.)*(xn--)?([a-z0-9][a-z0-9\-]{0,60}|[a-z0-9-]{1,30}\.[a-z]{2,})$#', $url)) {
                return $url;
            }
        }

        // Prevent //something/path parsed as full url with host.
        $url = ltrim($url, '/');

        $parts = parse_url($url);

        if (empty($parts['host'])) {
            return request()->getHost();
        }

        return $parts['host'];
    }

    /**
     * Get the current baseURL. The reason why we use this instead of request()->getHost()
     * is because we want to be able to mock the host in unit tests.
     * @param  null  $url
     * @return mixed
     */
    function getBaseUrl($url = null)
    {
        if (!$url) {
            $url = url()->current();
        }

        // Prevent //something/path parsed as full url with host.
        $url = ltrim($url, '/');

        $parts = parse_url($url);

        if (empty($parts['host'])) {
            return trim($this->configAppUrl, '/').'/';
        } else {
            if (empty($parts['scheme'])) {
                $parts['scheme'] = $this->configSSL ? 'https' : 'http';
            }
        }

        return $parts['scheme'].'://'.$parts['host'].'/';
    }

    /**
     * Get an URL with a proper local domain.
     *
     * @param $uri
     * @param  null  $locale
     * @return string
     */
    function absoluteUrl($uri, $locale = null)
    {
        $url = url($uri, [], $this->configSSL);

        if ($this->configLocaleDomains) {

            $locale = $locale ?? app()->getLocale();

            $domain = $this->getLocaleDomain($locale);

            if ($domain) {
                $url = preg_replace('#(?<=://)([^/]+?)(?=(/|\?|$))#u', $domain, $url);
            }
        }

        return $url;
    }
}