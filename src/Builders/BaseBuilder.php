<?php namespace ShvetsGroup\JetPages\Builders;

use ShvetsGroup\JetPages\Builders\Scanners\Scanner;
use ShvetsGroup\JetPages\Page\ArrayPageRegistry;

class BaseBuilder
{
    protected $scanners = [];
    protected $decorators = [];

    public function __construct($default_scanners = [], $default_decorators = []) {
        foreach ($default_scanners as $scanner => $paths) {
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
     * @throws ScannerPairIsInvalid
     */
    public function registerScanner($scanner, $paths)
    {
        if (!$paths || (!is_string($paths) && !is_array($paths))) {
            throw new ScannerPairIsInvalid('Scanner path should be a valid path or array of paths.');
        }
        $paths = is_array($paths) ? $paths : [$paths];
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                throw new ScannerPairIsInvalid("Scanner path should be a directory, '$path' given.");
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
     */
    public function build()
    {
        $tempRegistry = new ArrayPageRegistry();
        $this->scan($tempRegistry);
        $this->decorate($tempRegistry);
        $tempRegistry->save();
    }

    /**
     * Scan raw files for basic page information.
     * @param ArrayPageRegistry $registry
     */
    protected function scan(ArrayPageRegistry $registry)
    {
        foreach ($this->scanners as $scanner_pair) {
            /**
             * @var $scanner Scanner
             */
            $scanner = $scanner_pair['scanner'];
            if (is_string($scanner)) {
                $scanner = app()->make($scanner);
            }
            $pages = $scanner->scan($scanner_pair['paths']);
            $registry->add($pages);
        }
    }

    /**
     * Parse and decorate basic page objects.
     * @param ArrayPageRegistry $registry
     */
    protected function decorate(ArrayPageRegistry $registry)
    {
        foreach ($this->decorators as $decorator) {
            $decorator = app()->make($decorator);
            foreach ($registry->getAll() as $page) {
                $decorator->decorate($page, $registry);
            }
        }
    }
}