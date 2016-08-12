<?php namespace ShvetsGroup\JetPages\Builders;

use Illuminate\Http\Request;
use ShvetsGroup\JetPages\Page\Page;
use Symfony\Component\HttpFoundation\Response;

class StaticCache
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    public function __construct()
    {
        $this->files = app('Illuminate\Filesystem\Filesystem');
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function handleRequest(Request $request, Response $response)
    {
        if (env('APP_DEBUG', false)) return;
        if (!auth()->guest()) return;
        if (!$request->route()->getParameter('cache', true)) return;
        if ($response->getStatusCode() != 200) return;

        $content_type = $response->headers->get('Content-Type');
        $this->write($request->path(), $response->getContent(), starts_with($content_type, 'text/html'));
    }

    /**
     * Write cache for a given page.
     * @param Page $page
     */
    public function cachePage(Page $page) {
        if (env('APP_DEBUG', false)) return;
        if (!auth()->guest()) return;
        if (!$page->getAttribute('cache', true)) return;

        $path = $page->uri();
        $content = $page->render();
        $this->write($path, $content, true);
    }

    /**
     * Do the actual cache writing.
     * @param $path
     * @param $content
     * @param bool $is_html
     */
    public function write($path, $content, $is_html = true) {
        $cache_path = public_path('cache/' . $path);

        // Do not create file cache for very long filenames.
        foreach (explode('/', $cache_path) as $part) {
            if (strlen($part) >= 255) {
                return;
            }
        }

        if ($is_html) {
            if (!$this->files->isDirectory($cache_path)) {
                $this->files->makeDirectory($cache_path, 0777, true);
            }
            $content = "<!-- Cached on " . date('Y-m-d H-i-s') . " -->\n" . $content;
            $this->files->put($cache_path . '/index.html', $content);
        }
        else {
            if (!$this->files->isDirectory(dirname($cache_path))) {
                $this->files->makeDirectory(dirname($cache_path), 0777, true);
            }
            $this->files->put($cache_path, $content);
        }
    }
}