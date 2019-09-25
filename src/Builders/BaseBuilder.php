<?php

namespace ShvetsGroup\JetPages\Builders;

use ShvetsGroup\JetPages\Builders\Scanners\PageScanner;
use ShvetsGroup\JetPages\Builders\Scanners\Scanner;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;
use function ShvetsGroup\JetPages\content_path;

class BaseBuilder
{
    protected $files;
    protected $pageRegistry;
    protected $scanners = [];
    protected $parsers = [];
    protected $renderers = [];
    protected $postProcessors = [];
    protected $routesFile = null;

    public function __construct($pageRegistry = null, $scanners = [], $parsers = [], $renderers = [], $postProcessors = [])
    {
        $this->files = app('Illuminate\Filesystem\Filesystem');
        $this->pageRegistry = $pageRegistry ?: app('pages');
        $this->routesFile = storage_path('app/routes/routes.json');

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
            '\ShvetsGroup\JetPages\Builders\Renderers\IncludeRenderer',
            '\ShvetsGroup\JetPages\Builders\Renderers\MarkdownRenderer',
            '\ShvetsGroup\JetPages\Builders\Renderers\EscapePreTagRenderer',
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

    /**
     * Register scanners and their paths.
     *
     * @param $scanner
     * @param $paths
     * @throws BuilderException
     */
    public function registerScanner($scanner, $paths)
    {
        if (!$paths || (!is_string($paths) && !is_array($paths))) {
            throw new BuilderException('Scanner path should be a valid path or array of paths.');
        }
        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as &$path) {
            // Relative paths point to content directory.
            if (!$path || $path[0] !== '/') {
                $path = content_path($path);
            }
            if (!is_dir($path)) {
                throw new BuilderException("Scanner path should be a directory, '$path' given.");
            }
        }

        $this->scanners[] = [
            'scanner' => $scanner,
            'paths' => $paths,
        ];
    }

    /**
     * Register parser.
     *
     * @param $parser
     */
    public function registerParser($parser)
    {
        $this->parsers[] = $parser;
    }

    /**
     * Register renderer.
     *
     * @param $renderer
     */
    public function registerRenderer($renderer)
    {
        $this->renderers[] = $renderer;
    }

    /**
     * Register renderer.
     *
     * @param $post_processor
     */
    public function registerPostProcessor($post_processor)
    {
        $this->postProcessors[] = $post_processor;
    }

    // TODO replace with File::cleanDirectory($directory)
    public function deleteDir($path)
    {
        if (!file_exists($path)) {
            return false;
        }
        return is_file($path) ?
            @unlink($path) :
            array_map([$this, 'deleteDir'], glob($path.'/*')) == @rmdir($path);
    }

    /**
     * Build and save the page maps.
     * @param  bool  $reset
     * @param  array  $localeSlugsToReload
     */
    public function build($reset = false, $localeSlugsToReload = [])
    {
        if ($reset) {
            $this->pageRegistry->reset();
            if ($this->files->exists(dirname($this->routesFile))) {
                $this->files->deleteDirectories(dirname($this->routesFile));
            }
        }
        $this->pageRegistry->getAll();

        if (!$localeSlugsToReload) {
            $localeSlugsToReload = [];
        } else {
            if (!is_array($localeSlugsToReload)) {
                $localeSlugsToReload = [$localeSlugsToReload];
            }
        }

        $updatedPages = $this->scan($this->pageRegistry, $localeSlugsToReload);

        $this->do('parse', $this->pageRegistry, $updatedPages);
        $this->do('render', $this->pageRegistry, $updatedPages);
        $this->do('postProcess', $this->pageRegistry, $updatedPages);
        $this->pageRegistry->import($updatedPages);
        $this->pageRegistry->updateBuildTime();

        $routes = [];
        foreach ($this->pageRegistry->getAll() as $page) {
            if ($page->isPrivate()) {
                continue;
            }
            $routes[] = $page->locale.':'.$page->uri();
        }
        $this->files->makeDirectory(dirname($this->routesFile), 0755, true, true);
        $this->files->put($this->routesFile, json_encode($routes));
    }

    /**
     * @param  PageRegistry  $registry
     * @param  Page[]  $pages
     * @return Page[]
     */
    protected function getUpdatedPages(PageRegistry $registry, array $pages)
    {
        if (count($registry->getAll()) == 0) {
            return $pages;
        }

        $result = [];
        foreach ($pages as $localeSlug => $page) {
            if ($registry->needsUpdate($page)) {
                $result[$localeSlug] = $page;
            }
        }
        return $result;
    }

    /**
     * Scan raw files for basic page information.
     * @param  PageRegistry  $registry
     * @param  array  $localeSlugsToReload
     * @return Page[]
     */
    protected function scan(PageRegistry $registry, $localeSlugsToReload = [])
    {
        $result = [];

        foreach ($this->scanners as $scanner_pair) {
            $scanner = $this->makeScanner($scanner_pair);

            $scannedPages = [];
            foreach ($scanner_pair['paths'] as $path) {
                $scannedPages = $scanner->scanDirectory($path);
            }

            $existingByThisScanner = $registry->findAllBy('scanner', get_class($scanner));

            $absent = array_diff_key($existingByThisScanner, $scannedPages);
            foreach ($absent as $page) {
                $registry->delete($page);
            }

            $updatedPages = $this->getUpdatedPages($registry, $scannedPages);

            foreach ($localeSlugsToReload as $localeSlugToReload) {
                if (isset($scannedPages[$localeSlugToReload])) {
                    $updatedPages[$localeSlugToReload] = $scannedPages[$localeSlugToReload];
                }
            }

            $result = array_merge($result, $updatedPages);
        }

        $registry->addAll($result);

        return $result;
    }

    /**
     * @param  array  $scanner_pair
     * @return Scanner
     */
    private function makeScanner($scanner_pair)
    {
        $scanner = $scanner_pair['scanner'];
        if (is_string($scanner)) {
            $scanner = app()->make($scanner);
        }
        return $scanner;
    }

    /**
     * Scan raw files for basic page information.
     * @param  Page  $page
     * @return array|Page
     */
    protected function reScan($page)
    {
        if (!$page) {
            return null;
        }

        $filename = $page->getAttribute('path');
        if (!$filename) {
            return $page;
        }

        $filepath = dirname($filename);
        foreach ($this->scanners as $scanner_pair) {
            foreach ($scanner_pair['paths'] as $path) {
                if (mb_strpos($filepath, rtrim($path, '/').'/') !== false) {
                    $scanner = $this->makeScanner($scanner_pair);
                    return $scanner->scanFile($filename, $path);
                }
            }
        }
        return [];
    }

    /**
     * Parse and decorate basic page objects.
     * @param  string  $method
     * @param  PageRegistry  $registry
     * @param  Page[]  $pages
     */
    protected function do($method, PageRegistry $registry, $pages = [])
    {
        $pages = is_array($pages) ? $pages : [$pages];
        $lists = [
            'parse' => 'parsers',
            'render' => 'renderers',
            'postProcess' => 'postProcessors',
        ];

        if (empty($pages) && $method != 'postProcess') {
            return;
        }

        foreach ($this->{$lists[$method]} as $obj_name) {
            $obj = app()->make($obj_name);
            if ($method != 'postProcess') {
                foreach ($pages as $page) {
                    call_user_func([$obj, $method], $page, $registry);
                }
            } else {
                call_user_func([$obj, $method], $pages, $registry);
            }
            $registry->updateIndexes();
        }
    }
}