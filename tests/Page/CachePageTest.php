<?php namespace ShvetsGroup\Tests\JetPages\Page;

use ShvetsGroup\JetPages\Page\CachePage;

class CachePageTest extends AbstractPageTest
{
    public function setUp()
    {
        parent::setUp();
        $this->page = new CachePage();
    }
}
