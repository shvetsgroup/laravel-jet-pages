<?php

namespace ShvetsGroup\Tests\JetPages\Page;

use ShvetsGroup\JetPages\Page\SimplePageRegistry;

class SimplePageRegistryTest extends AbstractPageRegistryTest
{
    public function setUp(): void
    {
        parent::setUp();
        $this->registry = new SimplePageRegistry();
    }
}
