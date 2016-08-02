<?php namespace ShvetsGroup\JetPages\Builders;

use ShvetsGroup\JetPages\Builders\Scanners\Scanner;
use ShvetsGroup\JetPages\Builders\Scanners\PageScanner;
use ShvetsGroup\JetPages\Page\ArrayPageRegistry;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

class BaseBuilder
{
    protected $scanners = [];
    protected $decorators = [];

    public function __construct($default_scanners = [], $default_decorators = [])
    {
        $scanners = [];
        foreach ($default_scanners as $scanner => $paths) {
            if (!is_array($paths)) {
                $paths = [$paths];
            }
            if (is_numeric($scanner)) {
                $scanner = PageScanner::class;
            }
            if (isset($scanners[$scanner])) {
                $scanners[$scanner] = array_merge($scanners[$scanner], $paths);
            } else {
                $scanners[$scanner] = $paths;
            }
        }

        foreach ($scanners as $scanner => $paths) {
            $this->registerScanner($scanner, $paths);
        }
        foreach ($default_decorators as $decorator) {
            $this->registerDecorator($decorator);
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
            $path = \ShvetsGroup\JetPages\content_path($path);
            if (!is_dir($path)) {
                throw new BuilderException("Scanner path should be a directory, '$path' given.");
            }
        }

        $this->scanners[] = [
            'scanner' => $scanner,
            'paths' => $paths
        ];
    }

    /**
     * Register decorators.
     *
     * @param $decorator
     */
    public function registerDecorator($decorator)
    {
        $this->decorators[] = $decorator;
    }

    /**
     * Build and save the page maps.
     * @param bool $reset
     */
    public function build($reset = false)
    {
        $pages = $this->scan();

        $tempRegistry = new ArrayPageRegistry();
        $tempRegistry->add($pages);
        $this->decorate($tempRegistry);

        $pages = app('pages');
        if ($reset) {
            $pages->reset();
        }
        $pages->import($tempRegistry);
    }

    /**
     * Scan raw files for basic page information.
     */
    protected function scan()
    {
        $pages = [];
        foreach ($this->scanners as $scanner_pair) {
            $scanner = $this->makeScanner($scanner_pair);
            foreach ($scanner_pair['paths'] as $path) {
                $pages = array_merge($pages, $scanner->scanDirectory($path));
            }
        }
        return $pages;
    }

    /**
     * @param array $scanner_pair
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
     * Parse and decorate basic page objects.
     * @param PageRegistry $registry
     * @param array $pages
     */
    protected function decorate(PageRegistry $registry, $pages = [])
    {
        $state = [];
        $pages = $pages ?: $registry->getAll();
        foreach ($this->decorators as $decorator) {
            $decorator = app()->make($decorator);
            foreach ($pages as $page) {
                $decorator->decorate($page, $registry, $state);
            }
        }
    }

    /**
     * Rebuild a page from source.
     * @param $uri
     * @return Page
     */
    public function reBuild($uri)
    {
        $registry = app('pages');
        $page = $registry->findByUri($uri);

        if (!$page) {
            $this->build();
            return $registry->findByUriOrFail($uri);
        } else {
            $page = $this->reScan($page);
            abort_unless($page, 404);

            $this->reDecorate($page, $registry);
            $page->save();

            return $page;
        }
    }

    /**
     * Scan raw files for basic page information.
     * @param Page $page
     * @return Page
     */
    protected function reScan(Page $page)
    {
        $filename = $page->getAttribute('path');
        if (!$filename) {
            return $page;
        }

        $filepath = dirname($filename);
        foreach ($this->scanners as $scanner_pair) {
            foreach ($scanner_pair['paths'] as $path) {
                if (strpos($filepath, $path) !== false) {
                    $scanner = $this->makeScanner($scanner_pair);
                    $page = $scanner->scanFile($filename);
                    return $page;
                }
            }
        }
        return null;
    }

    /**
     * Parse and decorate basic page objects.
     * @param Page $page
     * @param PageRegistry $registry
     */
    protected function reDecorate(Page $page, PageRegistry $registry)
    {
        $this->decorate($registry, [$page]);
    }

}