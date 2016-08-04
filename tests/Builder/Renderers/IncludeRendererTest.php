<?php namespace ShvetsGroup\Tests\JetPages\Builders\Renderers;

use ShvetsGroup\JetPages\Builders\Renderers\Renderer;
use ShvetsGroup\JetPages\Builders\Renderers\IncludeRenderer;
use ShvetsGroup\JetPages\Page\ArrayPageRegistry;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class IncludeDecoratorTest extends AbstractTestCase
{
    /**
     * @var Renderer
     */
    private $renderer;
    private $pages;

    public function setUp()
    {
        parent::setUp();
        $this->renderer = new IncludeRenderer();
        $this->pages = new ArrayPageRegistry();
    }

    public function testDecorate()
    {
        $data = ['slug' => 'test', 'content' => "test\n!INCLUDE  \"includes/test.txt\"\ntest"];
        $page = new Page($data);
        $this->renderer->render($page, $this->pages);
        $this->assertEquals("test\ntest\ninclude\ntest", $page->getAttribute('content'));
    }

    /**
     * @expectedException \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function testIncludeDoesNotExist()
    {
        $data = ['slug' => 'test', 'content' => "test\n!INCLUDE  \"123\"\ntest"];
        $page = new Page($data);
        $this->renderer->render($page, $this->pages);
    }

}
