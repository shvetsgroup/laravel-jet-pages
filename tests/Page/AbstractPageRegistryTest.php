<?php namespace ShvetsGroup\Tests\JetPages\Page;

use Carbon\Carbon;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;
use ShvetsGroup\JetPages\Page\PageRegistry;

abstract class AbstractPageRegistryTest extends AbstractTestCase
{
    /**
     * @var PageRegistry
     */
    protected $registry;

    protected $data = [];

    public function setUp()
    {
        parent::setUp();
        $this->data = [
            'slug' => 'test',
            'title' => 'title',
            'content' => 'content'
        ];
    }

    public function testNewInstance()
    {
        $page = $this->registry->new($this->data);
        $this->assertPageEquals($this->data, $page);
        $this->assertEquals(null, $this->registry->findByUri($this->data['slug']));
    }

    /**
     * @expectedException \ShvetsGroup\JetPages\Page\PageException
     */
    public function testCreateEmpty()
    {
        $this->registry->createAndSave([]);
    }

    public function testCreate()
    {
        $page = $this->registry->createAndSave($this->data);
        $this->assertPageEquals($this->data, $page);
    }

    public function testFindByUri()
    {
        $this->registry->createAndSave($this->data);
        $page = $this->registry->findByUri($this->data['slug']);
        $this->assertPageEquals($this->data, $page);
    }

    public function testFindByUriIndex()
    {
        $data = ['slug' => 'index', 'payload' => '1'];
        $this->registry->createAndSave($data);
        $page = $this->registry->findByUri('/');
        $this->assertPageEquals($data, $page);

        $data = ['slug' => '/', 'payload' => '2'];
        $this->registry->createAndSave($data);
        $page = $this->registry->findByUri('/');
        $this->assertPageEquals(['slug' => 'index', 'payload' => '2'], $page);
    }

    public function testLastUpdated()
    {
        $this->assertEquals(0, $this->registry->lastUpdatedTime());
        $page = $this->registry->createAndSave(array_merge($this->data));
        $this->assertEquals($page->getAttribute('updated_at'), $this->registry->lastUpdatedTime());
        $first_updated = $page->updated_at = new Carbon('2011-01-01');
        $page->setAttribute('title', 'test')->save();
        $this->assertEquals($page->getAttribute('updated_at'), $this->registry->lastUpdatedTime());
        $this->assertNotEquals($page->getAttribute('updated_at'), $first_updated);
    }

    public function testIndex()
    {
        $page = $this->registry->createAndSave($this->data);
        $this->assertEquals(['test'], $this->registry->index());
        $page->slug = 'new';
        $page->save();
        $this->assertEquals(['new'], $this->registry->index());
        $page->delete();
        $this->assertEquals([], $this->registry->index());
    }

    public function testGetAll()
    {
        $page = $this->registry->createAndSave($this->data);
        $this->assertArrayHasKey($this->data['slug'], $this->registry->getAll());
        $page->slug = 'new';
        $page->save();
        $this->assertArrayHasKey('new', $this->registry->getAll());
        $this->assertArrayNotHasKey($this->data['slug'], $this->registry->getAll());
        $page->delete();
        $this->assertEquals([], $this->registry->getAll());
    }

}
