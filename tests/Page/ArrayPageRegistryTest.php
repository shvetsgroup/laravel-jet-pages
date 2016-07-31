<?php namespace ShvetsGroup\Tests\JetPages\Page;

use ShvetsGroup\JetPages\Page\ArrayPageRegistry;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;
use ShvetsGroup\JetPages\Page\PageRegistry;

class ArrayPageRegistryTest extends AbstractTestCase
{
    /**
     * @var PageRegistry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $index;

    /**
     * @var Page[]
     */
    protected $pages;

    public function setUp()
    {
        parent::setUp();
        $this->index = ['test1', 'test2', 'test3'];
        $this->pages = [];
        foreach ($this->index as $index) {
            $this->pages[$index] = app()->make('page', [['slug' => $index, 'title' => $index]]);
        }
        $this->registry = new ArrayPageRegistry($this->pages);
    }

    public function testFindByUri()
    {
        $index = $this->index[0];
        $this->assertEquals($this->pages[$index], $this->registry->findByUri($index));
    }

    public function testLastUpdated()
    {
        $this->assertEquals(0, $this->registry->lastUpdatedTime());
    }

    public function testIndex()
    {
        $this->assertEquals($this->index, $this->registry->index());
    }

    public function testGetAll()
    {
        $this->assertEquals($this->pages, $this->registry->getAll());
    }
}
