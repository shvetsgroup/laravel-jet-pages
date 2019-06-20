<?php

namespace ShvetsGroup\JetPages\Builders\PostProcessors;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;
use Symfony\Component\Yaml\Yaml;
use function \ShvetsGroup\JetPages\content_path;

class RedirectsPostProcessor implements PostProcessor
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Illuminate\Contracts\Cache\Store
     */
    private $cache;

    public function __construct()
    {
        $this->files = app('Illuminate\Filesystem\Filesystem');
        $this->cache = app('cache.store');
    }

    /**
     * @param Page[] $updatedPages
     * @param PageRegistry $registry
     */
    public function postProcess(array $updatedPages, PageRegistry $registry)
    {
        $path = content_path('redirects.yml');

        if ($this->files->exists($path)) {
            $redirects = Yaml::parse($this->files->get($path));

            $this->files->makeDirectory(storage_path('app/redirects'), 0755, true, true);
            $this->files->put(storage_path('app/redirects/redirects.json'),
                json_encode($redirects, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
        }
    }
}