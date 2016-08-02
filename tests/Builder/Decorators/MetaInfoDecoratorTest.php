<?php namespace ShvetsGroup\Tests\JetPages\Builders\Scanners;

use ShvetsGroup\JetPages\Builders\Decorators\Decorator;
use ShvetsGroup\JetPages\Builders\Decorators\MetaInfoDecorator;
use ShvetsGroup\JetPages\Page\ArrayPageRegistry;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class MetaInfoDecoratorTest extends AbstractTestCase
{
    /**
     * @var Decorator
     */
    private $decorator;
    private $data = [];
    private $pages;

    public function setUp()
    {
        parent::setUp();
        $this->decorator = new MetaInfoDecorator();
        $this->data = ['slug' => 'test', 'content' => "---\r\ntitle: Test\r\nslug: new-slug\n---\r\nContent"];
        $this->pages = new ArrayPageRegistry();
    }

    public function testDecorate()
    {
        $page = app()->make('page', [$this->data]);
        $this->decorator->decorate($page, $this->pages);
        $this->assertEquals('new-slug', $page->getAttribute('slug'));
        $this->assertEquals('Test', $page->getAttribute('title'));
        $this->assertEquals('Content', $page->getAttribute('content'));
    }

    public function testEmptyMetaData()
    {
        $data = ['slug' => 'test', 'content' => "---\r\n---\nContent"];
        $page = app()->make('page', [$data]);
        $this->decorator->decorate($page, $this->pages);
        $this->assertEquals('Content', $page->getAttribute('content'));
    }

    public function testEmptySrc()
    {
        $data = ['slug' => 'test'];
        $page = app()->make('page', [$data]);
        $this->decorator->decorate($page, $this->pages);
        $this->assertNull($page->getAttribute('content'));
    }

    /**
     * @dataProvider noMetaData
     */
    public function testNoMetaData($data)
    {
        $page = app()->make('page', [$data]);
        $this->decorator->decorate($page, $this->pages);
        $this->assertEquals($data['content'], $page->getAttribute('content'));
    }
    public function noMetaData()
    {
        return [
            [['slug' => 'test', 'content' => "Content"]],
            [['slug' => 'test', 'content' => "---Content"]],
            [['slug' => 'test', 'content' => "---\r\n---Content"]],
        ];
    }
}
