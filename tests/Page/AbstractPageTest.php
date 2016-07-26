<?php namespace ShvetsGroup\Tests\JetPages\Page;

use Carbon\Carbon;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;
use ShvetsGroup\JetPages\Page\CachePage;

abstract class AbstractPageTest extends AbstractTestCase
{
    /**
     * @var CachePage
     */
    protected $page;
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

    /**
     * @expectedException \ShvetsGroup\JetPages\Page\SlugIsRequired
     */
    public function testCreateEmpty()
    {
        $this->page->create([]);
    }

    public function testCreate()
    {
        $page = $this->page->create($this->data);
        $this->assertPageEquals($this->data, $page);
    }

    public function testAttributeOperations()
    {
        $page = $this->page->create($this->data);
        $this->assertEquals($this->data['title'], $page->getAttribute('title'));

        $page->setAttribute('title', '123');
        $this->assertEquals('123', $page->getAttribute('title'));

        $page->removeAttribute('title');
        $this->assertEquals(null, $page->getAttribute('title'));
    }

    public function testFindByUri()
    {
        $this->page->create($this->data);
        $page = $this->page->findByUri($this->data['slug']);
        $this->assertPageEquals($this->data, $page);
    }

    public function testFindByUriIndex()
    {
        $data = ['slug' => 'index', 'payload' => '1'];
        $this->page->create($data);
        $page = $this->page->findByUri('/');
        $this->assertPageEquals($data, $page);

        $data = ['slug' => '/', 'payload' => '2'];
        $this->page->create($data);
        $page = $this->page->findByUri('/');
        $this->assertPageEquals(['slug' => 'index', 'payload' => '2'], $page);
    }

    public function testLastUpdated()
    {
        $this->assertEquals(0, $this->page->lastUpdatedTime());
        $page = $this->page->create(array_merge($this->data));
        $this->assertEquals($page->updated_at, $this->page->lastUpdatedTime());
        $first_updated = $page->updated_at = new Carbon('2011-01-01');
        $page->setAttribute('title', 'test')->save();
        $this->assertEquals($page->updated_at, $this->page->lastUpdatedTime());
        $this->assertNotEquals($page->updated_at, $first_updated);
    }
}