<?php namespace ShvetsGroup\JetPages\Builders;

use ShvetsGroup\JetPages\Builders\Scanners\Scanner;
use ShvetsGroup\JetPages\Page\Pagelike;

class BaseBuilder
{
    protected $scanners = [];

    public function __construct($default_scanners = []) {
        foreach ($default_scanners as $scanner => $paths) {
            $this->registerScanner($scanner, $paths);
        }
    }

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

    public function build()
    {
        $this->scan();
    }

    protected function scan()
    {
        foreach ($this->scanners as $scanner_pair) {
            /**
             * @var $scanner Scanner
             */
            $scanner = $scanner_pair['scanner'];
            if (is_string($scanner)) {
                $scanner = app()->make($scanner);
            }
            $new = $scanner->scan($scanner_pair['paths']);
            $this->saveScanned($new);
        }
    }

    private function saveScanned(array $new)
    {
        foreach ($new as $data) {
            $page = app()->make(Pagelike::class);
            $page->fill($data)->save();
        }
    }
}