<?php namespace ShvetsGroup\Tests\JetPages\Page;

use ShvetsGroup\JetPages\Page\ArrayPageRegistry;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;
use ShvetsGroup\JetPages\Page\PageRegistry;

class ArrayPageRegistryTest extends AbstractPageRegistryTest
{
    public function setUp()
    {
        parent::setUp();
        $this->registry = new ArrayPageRegistry();
    }
}
