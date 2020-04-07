<?php

namespace ShvetsGroup\JetPages\PageBuilder\Scanners;

use Illuminate\Support\Collection;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;

interface Scanner
{
    /**
     * @return Collection
     */
    public function getPages(): PageCollection;

    /**
     * Discover all files for the future scan.
     * @return Collection
     */
    public function discoverAllFiles(): Collection;

    /**
     * @param  Collection  $files
     */
    public function processFiles(Collection $files);

    /**
     * @param  string  $directory
     * @return Collection
     */
    public function scanDirectory($directory);

    /**
     * @param  string  $filepath
     * @param  string  $directory
     * @return Collection|Page
     */
    public function scanFile($filepath, $directory);

}