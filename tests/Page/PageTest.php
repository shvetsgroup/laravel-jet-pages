<?php

namespace ShvetsGroup\Tests\JetPages\Page;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageException;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class PageTest extends AbstractTestCase
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
            'locale' => 'en',
            'slug' => 'test',
            'title' => 'title',
            'content' => 'content',
        ];
    }

    public function testAttributeOperations()
    {
        $page = new Page();
        $page->fill($this->data);
        $this->assertEquals($this->data['title'], $page->getAttribute('title'));

        $page->setAttribute('title', '123');
        $this->assertEquals('123', $page->getAttribute('title'));

        $page->removeAttribute('title');
        $this->assertEquals(null, $page->getAttribute('title'));
    }

    public function testLocaleSlugException()
    {
        $this->expectException(PageException::class);

        $page = new Page();
        $page->localeSlug();
    }

    public function testLocaleSlug()
    {
        $page = new Page($this->data);
        $this->assertEquals('en/test', $page->localeSlug());
    }
}
