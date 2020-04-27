<?php

namespace ShvetsGroup\JetPages\PageBuilder\PostProcessors;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Filesystem\Filesystem;
use ShvetsGroup\JetPages\Page\PageCollection;
use Symfony\Component\Yaml\Yaml;
use function ShvetsGroup\JetPages\content_path;

class RedirectsPostProcessor implements PostProcessor
{
    const REDIRECT_CACHE_PATH = 'app/jetpages/redirects.json';

    const CLEAN_JSON = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK;

    /**
     * @var Store
     */
    private $cache;

    /**
     * @var Filesystem
     */
    protected $files;

    protected $redirectCacheFile;

    public function __construct()
    {
        $this->cache = app('cache.store');
        $this->files = app('Illuminate\Filesystem\Filesystem');
        $this->redirectCacheFile = storage_path(static::REDIRECT_CACHE_PATH);
    }

    /**
     * @param  PageCollection  $updatedPages
     * @param  PageCollection  $pages
     */
    public function postProcess(PageCollection $updatedPages, PageCollection $pages)
    {
        $path = content_path('redirects.yml');

        if ($this->files->exists($path)) {
            $redirects = Yaml::parse($this->files->get($path));

            $this->cache->forever('jetpages:redirects', $redirects);

            $this->files->put($this->redirectCacheFile, json_encode($redirects, static::CLEAN_JSON));
        }
    }
}