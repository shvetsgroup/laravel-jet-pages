<?php namespace ShvetsGroup\Tests\JetPages\Page;

use ShvetsGroup\Tests\JetPages\AbstractTestCase;
use ShvetsGroup\JetPages\Page\Page;

abstract class AbstractPageTest extends AbstractTestCase
{
    /**
     * @var Page
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

    public function testAttributeOperations()
    {
        $page = $this->page->fill($this->data);
        $this->assertEquals($this->data['title'], $page->getAttribute('title'));

        $page->setAttribute('title', '123');
        $this->assertEquals('123', $page->getAttribute('title'));

        $page->removeAttribute('title');
        $this->assertEquals(null, $page->getAttribute('title'));
    }
}
