<?php namespace ShvetsGroup\Tests\JetPages\Page;

use ShvetsGroup\Tests\JetPages\AbstractTestCase;
use ShvetsGroup\JetPages\Page\Page;

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
            'content' => 'content'
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

    /**
     * @expectedException \ShvetsGroup\JetPages\Page\PageException
     */
    public function testLocaleSlugException()
    {
        $page = new Page();
        $page->localeSlug();
    }

    public function testLocaleSlug()
    {
        $page = new Page($this->data);
        $this->assertEquals('en/test', $page->localeSlug());
    }

    /**
     * @dataProvider dataNotIncludeDefaultLocaleInUrl
     */
    public function testNotIncludeDefaultLocaleInUrl($uri, $result)
    {
        app('config')->set(['jetpages.default_locale_in_url' => false]);
        $this->assertEquals($result, Page::uriToLocaleSlugArray($uri));
    }
    public function dataNotIncludeDefaultLocaleInUrl()
    {
        return [
            ['/', ['en', 'index']],
            ['en', ['en', 'index']],
            ['en/test', ['en', 'test']],
            ['ru', ['ru', 'index']],
            ['ru/test', ['ru', 'test']],
        ];
    }

    /**
     * @dataProvider dataIncludeDefaultLocaleInUrl
     */
    public function testIncludeDefaultLocaleInUrl($uri, $result)
    {
        app('config')->set(['jetpages.default_locale_in_url' => true]);
        $this->assertEquals($result, Page::uriToLocaleSlugArray($uri));
    }
    public function dataIncludeDefaultLocaleInUrl()
    {
        return [
            ['/', ['en', 'index']],
            ['en', ['en', 'en']],
            ['en/test', ['en', 'en/test']],
            ['ru', ['ru', 'index']],
            ['ru/test', ['ru', 'test']],
        ];
    }

    public function testSlugToUri()
    {
        $this->assertEquals('/', Page::slugToUri('index'));
    }

    public function testUriToSlug()
    {
        $this->assertEquals('index', Page::uriToSlug(''));
        $this->assertEquals('index', Page::uriToSlug('/'));
    }

    public function makeLocaleUri() {
        $this->assertEquals('/', Page::makeLocaleUri(null, 'index'));
        $this->assertEquals('/', Page::makeLocaleUri('en', 'index'));
        $this->assertEquals('ru', Page::makeLocaleUri('ru', 'index'));
    }
}
