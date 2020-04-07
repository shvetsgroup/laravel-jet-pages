<?php

namespace ShvetsGroup\Tests\JetPages\Page;

use Carbon\Carbon;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class PageTest extends AbstractTestCase
{
    /**
     * @var Page
     */
    protected $page;
    protected $testAttributes = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->testAttributes = [
            'locale' => 'en',
            'slug' => 'test',
            'title' => 'title',
            'content' => 'content',
        ];
    }

    public function testAttributeOperations()
    {
        $page = new Page();
        $page->fill($this->testAttributes);
        $page->save();
        $this->assertEquals($this->testAttributes['title'], $page->title);
        $this->assertEquals($this->testAttributes['title'], $page->getAttribute('title'));

        $page->title = '123';
        $this->assertEquals('123', $page->title);
        $this->assertEquals('123', $page->getAttribute('title'));

        $page->content_footer = '321';
        $this->assertEquals('321', $page->content_footer);

        $page->data = ['test' => 1];
        $this->assertEquals(['test' => 1], $page->data);

        $now = Carbon::now()->startOfSecond();
        $page->updated_at = $now;
        $this->assertEquals($now, $page->updated_at);
        $page->save();
        $page->fresh();
        $this->assertEquals($now, $page->updated_at);
        $this->assertEquals(['test' => 1], $page->data);
    }

    public function testLocaleSlug()
    {
        $page = new Page($this->testAttributes);
        $this->assertEquals('en/test', $page->localeSlug);
        $this->assertEquals('test', $page->uri);
        $this->assertEquals('http://localhost/test', $page->url);
        $this->assertEquals('/test', $page->url_without_domain);

        $page->locale = 'ru';
        $this->assertEquals('ru/test', $page->localeSlug);
        $this->assertEquals('ru/test', $page->uri);
        $this->assertEquals('http://localhost/ru/test', $page->url);
        $this->assertEquals('/ru/test', $page->url_without_domain);

        $page->slug = 'index';
        $this->assertEquals('ru/index', $page->localeSlug);
        $this->assertEquals('ru', $page->uri);
        $this->assertEquals('http://localhost/ru', $page->url);
        $this->assertEquals('/ru', $page->url_without_domain);
    }

    public function testGetContentAttributes()
    {
        $page = new Page($this->testAttributes);
        $page->content_description = '123';

        $this->assertEquals(['content', 'content_description'], $page->getContentAttributes());
    }

    public function testPageRender()
    {
        $page = new Page($this->testAttributes);
        $this->assertEquals(<<<HTML
<h1>title</h1>
<div class="content">
    content
</div>
HTML
            , $page->render());
    }
}
