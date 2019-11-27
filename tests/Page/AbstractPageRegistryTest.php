<?php

namespace ShvetsGroup\Tests\JetPages\Page;

use Carbon\Carbon;
use ShvetsGroup\JetPages\Page\PageException;
use ShvetsGroup\JetPages\Page\PageRegistry;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

abstract class AbstractPageRegistryTest extends AbstractTestCase
{
    /**
     * @var PageRegistry
     */
    protected $registry;

    protected $data = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->data = [
            'locale' => 'en',
            'slug' => 'test',
            'title' => 'title',
            'content' => 'content',
            'private' => false,
        ];
    }

    public function testNewInstance()
    {
        $page = $this->registry->new($this->data);
        $this->assertPageEquals($this->data, $page);
        $this->assertEquals(null, $this->registry->findByUri($this->data['slug']));
    }

    public function testCreateEmpty()
    {
        $this->expectException(PageException::class);
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
        $this->assertPageEquals($data + ['locale' => 'en', 'private' => false], $page);

        $data = ['slug' => '/', 'payload' => '2'];
        $this->registry->createAndSave($data);
        $page = $this->registry->findByUri('/');
        $this->assertPageEquals(['slug' => 'index', 'payload' => '2', 'locale' => 'en', 'private' => false], $page);
    }

    public function testLastUpdated()
    {
        $this->assertEquals(0, $this->registry->lastUpdatedTime());
        $page = $this->registry->createAndSave(array_merge($this->data));
        $this->assertEquals($page->getAttribute('updated_at'), $this->registry->lastUpdatedTime());
        $first_updated = $page->updated_at = (new Carbon('2011-01-01'))->format('Y-m-d H:i:s');
        $this->registry->save($page->setAttribute('title', 'test'));
        $this->assertEquals($page->getAttribute('updated_at'), $this->registry->lastUpdatedTime());
    }

    public function testIndex()
    {
        $page = $this->registry->createAndSave($this->data);
        $this->assertEquals(['en/test' => $page->updated_at], $this->registry->index());
        $page->slug = 'new';
        $this->registry->save($page);
        $this->assertEquals(['en/new' => $page->updated_at], $this->registry->index());
        $this->registry->delete($page);
        $this->assertEquals([], $this->registry->index());
    }

    public function testGetAll()
    {
        $page = $this->registry->createAndSave($this->data);
        $this->assertArrayHasKey('en/'.$this->data['slug'], $this->registry->getAll());
        $page->slug = 'new';
        $this->registry->save($page);
        $this->assertArrayHasKey('en/new', $this->registry->getAll());
        $this->assertArrayNotHasKey('en/'.$this->data['slug'], $this->registry->getAll());
        $this->registry->delete($page);
        $this->assertEquals([], $this->registry->getAll());
    }

}
