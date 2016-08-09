<?php namespace ShvetsGroup\JetPages\Builders\PostProcessors;

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
            foreach ($redirects as $redirect => $destination) {
                $this->cache->forever("redirect:{$redirect}", $destination);
            }
        }
    }
}