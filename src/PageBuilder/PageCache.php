<?php

namespace ShvetsGroup\JetPages\PageBuilder;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ShvetsGroup\JetPages\Page\Page;
use Symfony\Component\HttpFoundation\Response;

class PageCache
{
    /**
     * @var Filesystem
     */
    protected $files;

    public function __construct()
    {
        $this->files = app('Illuminate\Filesystem\Filesystem');
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  null  $cache_bag
     * @return bool
     */
    public function handleRequest(Request $request, Response $response, $cache_bag = null)
    {
        if (config('app.debug', false)) {
            return false;
        }
        if (!auth()->guest()) {
            return false;
        }
        if (!$request->route()->parameter('cache', true)) {
            return false;
        }
        if ($request->method() != 'GET') {
            return false;
        }
        if ($request->query()) {
            return false;
        }
        if ($response->getStatusCode() != 200) {
            return false;
        }

        $path = $request->path();
        $content = $response->getContent();
        $content_type = $response->headers->get('Content-Type');
        $this->write($path, $content, Str::startsWith($content_type, 'text/html'), $cache_bag);

        return true;
    }

    /**
     * Write cache for a given page.
     * @param  Page  $page
     * @param  bool  $force
     */
    public function cachePage(Page $page, $force = false)
    {
        if (config('app.debug', false) && !$force) {
            return;
        }
        if (!auth()->guest()) {
            return;
        }
        if ($page->isPrivate()) {
            return;
        }
        if (!$page->getAttribute('cache')) {
            return;
        }

        $content = $page->render();
        $this->write($page->getAttribute('uri'), $content, true, $page->getAttribute('cache_bag'));
    }

    /**
     * Do the actual cache writing.
     * @param $path
     * @param $content
     * @param  bool  $is_html
     * @param  null  $cache_bag
     */
    public function write($path, $content, $is_html = true, $cache_bag = null)
    {
        $cache_dir = config('jetpages.static_cache_public_dir', 'cache');
        $cache_bag = $cache_bag ?? config('jetpages.default_cache_bag', 'default');
        $cache_path = public_path($cache_dir.'/'.$cache_bag.'/'.$path);

        // Do not create file cache for very long filenames.
        foreach (explode('/', $cache_path) as $part) {
            if (mb_strlen($part) >= 255) {
                return;
            }
        }

        if ($is_html) {
            if (!$this->files->isDirectory($cache_path)) {
                $this->files->makeDirectory($cache_path, 0777, true);
            }
            $this->files->put($cache_path.'/index.html', $content);
        } else {
            if (!$this->files->isDirectory(dirname($cache_path))) {
                $this->files->makeDirectory(dirname($cache_path), 0777, true);
            }
            $this->files->put($cache_path, $content);
        }
    }
}