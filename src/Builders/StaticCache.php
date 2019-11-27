<?php

namespace ShvetsGroup\JetPages\Builders;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ShvetsGroup\JetPages\Page\Page;
use Symfony\Component\HttpFoundation\Response;

class StaticCache
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
     */
    public function handleRequest(Request $request, Response $response)
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
        $this->write($path, $content, Str::startsWith($content_type, 'text/html'));

        return true;
    }

    /**
     * Write cache for a given page.
     * @param  Page  $page
     */
    public function cachePage(Page $page)
    {
        if (config('app.debug', false)) {
            return;
        }
        if (!auth()->guest()) {
            return;
        }
        if (!$page->getAttribute('cache', true)) {
            return;
        }

        $path = $page->uri();
        $content = $page->render();
        $this->write($path, $content, true);
    }

    /**
     * Do the actual cache writing.
     * @param $path
     * @param $content
     * @param  bool  $is_html
     */
    public function write($path, $content, $is_html = true)
    {
        $cache_dir = config('jetpages.cache_dir', 'cache');
        $cache_path = public_path($cache_dir.'/'.$path);

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
            $content = $content."<!-- Cached on ".date('Y-m-d H-i-s')." -->\n";
            $this->files->put($cache_path.'/index.html', $content);
        } else {
            if (!$this->files->isDirectory(dirname($cache_path))) {
                $this->files->makeDirectory(dirname($cache_path), 0777, true);
            }
            $this->files->put($cache_path, $content);
        }
    }
}