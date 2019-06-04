<?php

namespace ShvetsGroup\Tests\JetPages\Page;

use ShvetsGroup\JetPages\Page\SimplePageRegistry;

class SimplePageRegistryTest extends AbstractPageRegistryTest
{
    public function setUp()
    {
        parent::setUp();
        $this->registry = new SimplePageRegistry();
    }
}
