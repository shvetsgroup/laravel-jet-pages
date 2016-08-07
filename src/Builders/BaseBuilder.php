<?php namespace ShvetsGroup\JetPages\Builders;

use ShvetsGroup\JetPages\Builders\Scanners\Scanner;
use ShvetsGroup\JetPages\Builders\Scanners\PageScanner;
use ShvetsGroup\JetPages\Page\ArrayPageRegistry;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;

class BaseBuilder
{
    protected $scanners = [];
    protected $parsers = [];
    protected $renderers = [];
    protected $postProcessors = [];

    public function __construct($defaultScanners = [], $defaultParsers = [], $defaultRenderers = [], $defaultPostProcessors = [])
    {
        $scanners = [];
        foreach ($defaultScanners as $scanner => $paths) {
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
        foreach ($defaultParsers as $parser) {
            $this->registerParser($parser);
        }
        foreach ($defaultRenderers as $renderer) {
            $this->registerRenderer($renderer);
        }
        foreach ($defaultPostProcessors as $postProcessor) {
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

    /**
     * Build and save the page maps.
     * @param bool $reset
     */
    public function build($reset = false)
    {
        $persistentRegistry = app('pages');
        if ($reset) {
            $persistentRegistry->reset();
        }
        $tempRegistry = new ArrayPageRegistry();
        $pages = $this->scan($tempRegistry);
        //$pages = $this->filterUpdated($persistentRegistry, $pages);
        $this->do('parse', $tempRegistry, $pages);
        $this->do('render', $tempRegistry, $pages);
        $this->do('postProcess', $tempRegistry, $pages);
        $persistentRegistry->import($pages);
    }

    /**
     * @param PageRegistry $registry
     * @param Page[] $pages
     * @return Page[]
     */
    protected function filterUpdated(PageRegistry $registry, array $pages) {
        $result = [];
        foreach ($pages as $page) {
            if ($registry->needsUpdate($page)) {
                $result[] = $page;
            }
        }
        return $result;
    }

    /**
     * Scan raw files for basic page information.
     * @param PageRegistry $registry
     * @return Page[]
     */
    protected function scan(PageRegistry $registry)
    {
        $pages = [];
        foreach ($this->scanners as $scanner_pair) {
            $scanner = $this->makeScanner($scanner_pair);
            foreach ($scanner_pair['paths'] as $path) {
                $pages = array_merge($pages, $scanner->scanDirectory($path));
            }
        }
        $registry->import($pages);
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
     * @param string $method
     * @param PageRegistry $registry
     * @param Page[] $pages
     */
    protected function do($method, PageRegistry $registry, $pages = [])
    {
        $lists = [
            'parse' => 'parsers',
            'render' => 'renderers',
            'postProcess' => 'postProcessors',
        ];
        foreach ($this->{$lists[$method]} as $obj_name) {
            $obj = app()->make($obj_name);
            foreach ($pages as $page) {
                call_user_func(array($obj, $method), $page, $registry);
                $page->setAttribute('build__' . $method . '__' . $obj_name, true);
            }
        }
    }
}