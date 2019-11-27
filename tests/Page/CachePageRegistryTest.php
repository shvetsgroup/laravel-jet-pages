<?php

namespace ShvetsGroup\Tests\JetPages\Page;

use ShvetsGroup\JetPages\Page\CachePageRegistry;

class CachePageRegistryTest extends AbstractPageRegistryTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->registry = new CachePageRegistry();
    }
}
