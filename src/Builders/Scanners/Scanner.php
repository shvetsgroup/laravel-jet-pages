<?php namespace ShvetsGroup\JetPages\Builders\Scanners;

use ShvetsGroup\JetPages\Page\Page;

interface Scanner
{
    /**
     * @param string $directory
     * @return Page[]
     */
    public function scan($directory);
}