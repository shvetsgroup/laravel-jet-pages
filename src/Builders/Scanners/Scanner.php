<?php namespace ShvetsGroup\JetPages\Builders\Scanners;

use ShvetsGroup\JetPages\Page\Page;

interface Scanner
{
    /**
     * @param string $directory
     * @return Page[]
     */
    public function scanDirectory($directory);

    /**
     * @param string $filename
     * @return Page
     */
    public function scanFile($filename);

}