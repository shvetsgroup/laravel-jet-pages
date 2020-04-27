<?php

namespace ShvetsGroup\JetPages\PageBuilder;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;
use ShvetsGroup\JetPages\Page\PageQuery;
use ShvetsGroup\JetPages\PageBuilder\Scanners\PageScanner;
use ShvetsGroup\JetPages\PageBuilder\Scanners\Scanner;
use function ShvetsGroup\JetPages\content_path;

class PageBuilder
{
    const JETPAGES_DIR = 'app/jetpages';
    const ROUTES_CACHE_PATH = 'app/jetpages/routes.json';

    /**
     * @var Store
     */
    protected $cache;

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var PageCollection
     */
    protected $pages;

    /**
     * @var PageCollection
     */
    protected $updatedPages;

    protected $forcePagesToRebuild = [];

    protected $scanners = [];

    protected $parsers = [];

    protected $renderers = [];

    protected $postProcessors = [];

    protected $routesCacheFile;
    protected $routesCacheDir;

    public function __construct($scanners = [], $parsers = [], $renderers = [], $postProcessors = [])
    {
        $this->cache = app('cache.store');
        $this->files = app('Illuminate\Filesystem\Filesystem');
        $this->jetpagesDir = storage_path(static::JETPAGES_DIR);
        $this->routesCacheFile = storage_path(static::ROUTES_CACHE_PATH);
        $this->routesCacheDir = dirname($this->routesCacheFile);

        $scanners = $scanners ?: config('jetpages.content_scanners', ['pages']);
        $scanners = is_array($scanners) ? $scanners : [$scanners];
        $processedScanners = [];
        foreach ($scanners as $scanner => $paths) {
            if (!is_array($paths)) {
                $paths = [$paths];
            }
            if (is_numeric($scanner)) {
                $scanner = PageScanner::class;
            }
            if (isset($processedScanners[$scanner])) {
                $processedScanners[$scanner] = array_merge($processedScanners[$scanner], $paths);
            } else {
                $processedScanners[$scanner] = $paths;
            }
        }
        foreach ($processedScanners as $scanner => $paths) {
            $this->registerScanner($scanner, $paths);
        }

        $parsers = $parsers ?: config('jetpages.content_parsers', [
            '\ShvetsGroup\JetPages\Builders\Parsers\MetaInfoParser',
            '\ShvetsGroup\JetPages\Builders\Parsers\NavigationParser',
            '\ShvetsGroup\JetPages\Builders\Parsers\BreadcrumbParser',
        ]);
        $parsers = is_array($parsers) ? $parsers : [$parsers];
        foreach ($parsers as $parser) {
            $this->registerParser($parser);
        }

        $renderers = $renderers ?: config('jetpages.content_renderers', [
            '\ShvetsGroup\JetPages\PageBuilder\Renderers\IncludeRenderer',
            '\ShvetsGroup\JetPages\PageBuilder\Renderers\MarkdownRenderer',
            '\ShvetsGroup\JetPages\PageBuilder\Renderers\EscapePreTagRenderer',
        ]);
        $renderers = is_array($renderers) ? $renderers : [$renderers];
        foreach ($renderers as $renderer) {
            $this->registerRenderer($renderer);
        }

        $postProcessors = $postProcessors ?: config('jetpages.content_post_processors', [
            '\ShvetsGroup\JetPages\Builders\PostProcessors\MenuPostProcessor',
            '\ShvetsGroup\JetPages\Builders\PostProcessors\RedirectsPostProcessor',
            '\ShvetsGroup\JetPages\Builders\PostProcessors\StaticCachePostProcessor',
        ]);
        $postProcessors = is_array($postProcessors) ? $postProcessors : [$postProcessors];
        foreach ($postProcessors as $postProcessor) {
            $this->registerPostProcessor($postProcessor);
        }
    }

    public function registerScanner($scanner, $paths)
    {
        if (!$paths || (!is_string($paths) && !is_array($paths))) {
            throw new PageBuilderException('Scanner path should be a valid path or array of paths.');
        }

        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as &$path) {
            // Relative paths point to content directory.
            if (!$path || $path[0] !== '/') {
                $path = content_path($path);
            }
            if (!is_dir($path)) {
                throw new PageBuilderException("Scanner path should be a directory, '$path' given.");
            }
        }

        $this->scanners[] = [
            'scanner' => $scanner,
            'paths' => $paths,
        ];
    }

    public function registerParser($parser)
    {
        $this->parsers[] = $parser;
    }

    public function registerRenderer($renderer)
    {
        $this->renderers[] = $renderer;
    }

    public function registerPostProcessor($post_processor)
    {
        $this->postProcessors[] = $post_processor;
    }

    /**
     * Reset all pages before a build.
     */
    public function reset()
    {
        $this->pages = null;

        PageQuery::truncate();

        $this->cache->forget('jetpages:routes');
        $this->cache->forget('jetpages:redirects');
        $this->cache->forget('jetpages:scans');
        $this->cache->forget('jetpages:jetpages:menu_timestamps');

        $locales = config('laravellocalization.supportedLocales') ?: [config('app.default_locale') => []];
        foreach ($locales as $locale => $data) {
            $this->cache->forget('jetpages:menu:'.$locale);
        }

        if ($this->files->exists($this->jetpagesDir)) {
            $this->files->deleteDirectory($this->jetpagesDir);
        }
    }

    public function forcePagesToRebuild($localeSlugs = [])
    {
        $this->forcePagesToRebuild = $localeSlugs;
    }

    /**
     * Build and save the page maps.
     */
    public function build()
    {
        $this->loadPages();

        $this->scan();

        $this->parse();

        $this->render();

        $this->updatedPages->each(function (Page $page) {
            $page->save();
        });

        $this->postProcess();

        $this->updateBuildHash();

        $this->updateCaches();
    }

    private function loadPages()
    {
        $this->pages = PageQuery::get();
    }

    public function scan()
    {
        $this->updatedPages = new PageCollection();

        $lastScans = $this->cache->get('jetpages:scans');

        $pagesByScanner = $this->pages->groupBy('scanner', true);

        foreach ($this->scanners as $scanner_pair) {
            $scanner = $this->makeScanner($scanner_pair);
            $files = $scanner->discoverAllFiles();

            $scannerClass = get_class($scanner);
            $pagesOfThisScanner = $pagesByScanner->get($scannerClass, []);

            foreach ($pagesOfThisScanner as $localeSlug => $page) {
                $path = $page->getAttribute('path');
                if (!$files->has($path)) {
                    $this->pages->delete($localeSlug);
                }
            }

            if ($this->forcePagesToRebuild) {
                $forceUpdatePaths = $pagesOfThisScanner->whereIn('localeSlug', $this->forcePagesToRebuild)->pluck('localeSlug', 'path');
            }

            $lastScanFiles = $lastScans[$scannerClass] ?? new Collection();

            if ($lastScanFiles->count()) {
                foreach ($files as $key => $file) {
                    if (isset($forceUpdatePaths) && $forceUpdatePaths->has($key)) {
                        continue;
                    }

                    if ($lastScanFiles->has($key)) {
                        $last = $lastScanFiles->get($key);
                        if ($last >= $file->timestamp) {
                            $files->forget($key);
                        }
                    }
                }
            }

            $up = $files->pluck('timestamp', 'path');
            $lastScanFiles = $lastScanFiles->merge($up);
            $lastScans[$scannerClass] = $lastScanFiles;

            $scanner->processFiles($files);

            $this->updatedPages = $this->updatedPages->merge($scanner->getPages());
        }
        $this->cache->forever('jetpages:scans', $lastScans);

        if ($this->pages->isNotEmpty()) {
            $this->updatedPages->each(function (Page $page) {
                $pPage = $this->pages->get($page->getAttribute('localeSlug'));
                if ($pPage) {
                    $page->exists = true;
                    $page->setAttribute('id', $pPage->getAttribute('id'));
                }
            });
        }

        $this->pages = $this->pages->merge($this->updatedPages);
    }

    private function makeScanner($scanner_pair): Scanner
    {
        $scanner = $scanner_pair['scanner'];
        if (is_string($scanner)) {
            $scanner = new $scanner($scanner_pair['paths']);
        }
        return $scanner;
    }

    private function parse()
    {
        foreach ($this->parsers as $parser) {
            $obj = new $parser();
            foreach ($this->updatedPages as $page) {
                $obj->parse($page, $this->pages);
            }
        }
    }

    private function render()
    {
        foreach ($this->renderers as $renderer) {
            $obj = new $renderer();
            foreach ($this->updatedPages as $page) {
                $obj->render($page, $this->pages);
            }
        }
    }

    private function postProcess()
    {
        foreach ($this->postProcessors as $postProcessor) {
            $obj = new $postProcessor();
            $obj->postProcess($this->updatedPages, $this->pages);
        }
    }

    public function getBuildHash()
    {
        $hashFile = storage_path('app/jetpages/content_hash/build.json');

        if (!file_exists($hashFile)) {
            $this->updateBuildHash();
        }

        return json_decode(file_get_contents($hashFile));
    }

    protected function updateBuildHash()
    {
        if (!isset($this->pages)) {
            $this->loadPages();
        }

        $hash = '';
        $this->pages->each(function (Page $page) use (&$hash) {
            $hash = $hash.$page->getAttribute('hash');
        });
        $hash = md5($hash);

        $this->files->makeDirectory(storage_path('app/jetpages/content_hash'), 0755, true, true);
        $this->files->put(storage_path('app/jetpages/content_hash/build.json'), json_encode($hash));

        return $hash;
    }

    protected function updateCaches()
    {
        $routes = $this->pages->mapWithKeys(function (Page $page) {
            return [$page->getAttribute('locale').':'.$page->getAttribute('uri') => $page->isPublic()];
        });

        $this->cache->forever('jetpages:routes', $routes);
        $this->files->makeDirectory($this->routesCacheDir, 0755, true, true);
        $this->files->put($this->routesCacheFile, json_encode($routes));
    }
}