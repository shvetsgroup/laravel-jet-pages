<?php namespace ShvetsGroup\Tests\JetPages\Builders\Scanners;

use ShvetsGroup\JetPages\Builders\Decorators\Decorator;
use ShvetsGroup\JetPages\Builders\Decorators\IncludeDecorator;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class IncludeDecoratorTest extends AbstractTestCase
{
    /**
     * @var Decorator
     */
    private $decorator;

    public function setUp()
    {
        parent::setUp();
        $this->decorator = new IncludeDecorator();
    }

    public function testDecorate()
    {
        $data = ['slug' => 'test', 'src' => "test\n!INCLUDE  \"includes/test.txt\"\ntest"];
        $page = app()->make('page', [$data]);
        $this->decorator->decorate($page);
        $this->assertEquals("test\ntest\ninclude\ntest", $page->getAttribute('src'));
    }

    /**
     * @expectedException \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testIncludeDoesNotExist()
    {
        $data = ['slug' => 'test', 'src' => "test\n!INCLUDE  \"123\"\ntest"];
        $page = app()->make('page', [$data]);
        $this->decorator->decorate($page);
    }

}
