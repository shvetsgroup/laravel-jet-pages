<?php namespace ShvetsGroup\Tests\JetPages\Builders\Scanners;

use ShvetsGroup\JetPages\Builders\Decorators\Decorator;
use ShvetsGroup\JetPages\Builders\Decorators\Content\IncludeDecorator;
use ShvetsGroup\JetPages\Page\ArrayPageRegistry;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class IncludeDecoratorTest extends AbstractTestCase
{
    /**
     * @var Decorator
     */
    private $decorator;
    private $pages;

    public function setUp()
    {
        parent::setUp();
        $this->decorator = new IncludeDecorator();
        $this->pages = new ArrayPageRegistry();
    }

    public function testDecorate()
    {
        $data = ['slug' => 'test', 'content' => "test\n!INCLUDE  \"includes/test.txt\"\ntest"];
        $page = app()->make('page', [$data]);
        $this->decorator->decorate($page, $this->pages);
        $this->assertEquals("test\ntest\ninclude\ntest", $page->getAttribute('content'));
    }

    /**
     * @expectedException \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testIncludeDoesNotExist()
    {
        $data = ['slug' => 'test', 'content' => "test\n!INCLUDE  \"123\"\ntest"];
        $page = app()->make('page', [$data]);
        $this->decorator->decorate($page, $this->pages);
    }

}
