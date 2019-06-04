<?php

namespace ShvetsGroup\JetPages\Builders\Scanners;

use ShvetsGroup\JetPages\Page\Page;

interface Scanner
{
    /**
     * @param string $directory
     * @return Page[]
     */
    public function scanDirectory($directory);

    /**
     * @param string $filepath
     * @param string $directory
     * @return array|Page
     */
    public function scanFile($filepath, $directory);

}