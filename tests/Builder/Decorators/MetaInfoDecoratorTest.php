<?php namespace ShvetsGroup\Tests\JetPages\Builders\Scanners;

use ShvetsGroup\JetPages\Builders\Decorators\Decorator;
use ShvetsGroup\JetPages\Builders\Decorators\MetaInfoDecorator;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class MetaInfoDecoratorTest extends AbstractTestCase
{
    /**
     * @var Decorator
     */
    private $decorator;
    private $data = [];

    public function setUp()
    {
        parent::setUp();
        $this->decorator = new MetaInfoDecorator();
        $this->data = ['slug' => 'test', 'src' => "---\r\ntitle: Test\r\nslug: new-slug\n---\r\nContent"];
    }

    public function testDecorate()
    {
        $page = app()->make('page', [$this->data]);
        $this->decorator->decorate($page);
        $this->assertEquals('new-slug', $page->getAttribute('slug'));
        $this->assertEquals('Test', $page->getAttribute('title'));
        $this->assertEquals('Content', $page->getAttribute('src'));
    }

    public function testEmptyMetaData()
    {
        $data = ['slug' => 'test', 'src' => "---\r\n---\nContent"];
        $page = app()->make('page', [$data]);
        $this->decorator->decorate($page);
        $this->assertEquals('Content', $page->getAttribute('src'));
    }

    public function testEmptySrc()
    {
        $data = ['slug' => 'test'];
        $page = app()->make('page', [$data]);
        $this->decorator->decorate($page);
        $this->assertNull($page->getAttribute('src'));
    }

    /**
     * @dataProvider noMetaData
     */
    public function testNoMetaData($data)
    {
        $page = app()->make('page', [$data]);
        $this->decorator->decorate($page);
        $this->assertEquals($data['src'], $page->getAttribute('src'));
    }
    public function noMetaData()
    {
        return [
            [['slug' => 'test', 'src' => "Content"]],
            [['slug' => 'test', 'src' => "---Content"]],
            [['slug' => 'test', 'src' => "---\r\n---Content"]],
        ];
    }
}
