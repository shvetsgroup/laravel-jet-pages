<?php namespace ShvetsGroup\JetPages\Builders;

use ShvetsGroup\JetPages\Builders\Scanners\Scanner;

class BaseBuilder
{
    protected $scanners = [];

    public function registerScanner(Scanner $scanner, $path)
    {
        $this->scanners[] = [
            'scanner' => $scanner,
            'path' => $path
        ];
    }

    public function build()
    {
        $this->scan();
    }

    public function scan()
    {
        foreach ($this->scanners as $scanner_pair) {
            $new = $scanner_pair['scanner']->scan($scanner_pair['path']);
            $this->saveScanned($new);
        }
    }

    private function saveScanned(array $new)
    {

    }

    private function getContentDirectory()
    {
        return config('jetpages.content_dir', resource_path('content'));
    }

}