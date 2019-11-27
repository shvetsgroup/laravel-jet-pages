<?php

namespace ShvetsGroup\Tests\JetPages\Builders\Renderers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use ShvetsGroup\JetPages\Builders\Renderers\IncludeRenderer;
use ShvetsGroup\JetPages\Builders\Renderers\Renderer;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\SimplePageRegistry;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class IncludeRendererTest extends AbstractTestCase
{
    /**
     * @var Renderer
     */
    private $renderer;
    private $pages;

    public function setUp(): void
    {
        parent::setUp();
        $this->linkFixtureContent();
        $this->renderer = new IncludeRenderer();
        $this->pages = new SimplePageRegistry();
    }

    public function testRender()
    {
        $data = ['slug' => 'test', 'content' => "test\n!INCLUDE  \"includes/test.txt\"\ntest"];
        $page = new Page($data);
        $this->renderer->render($page, $this->pages);
        $this->assertEquals("test\ntest\ninclude\ntest", $page->getAttribute('content'));
    }

    public function testIncludeDoesNotExist()
    {
        $this->expectException(FileNotFoundException::class);

        $data = ['slug' => 'test', 'content' => "test\n!INCLUDE  \"123\"\ntest"];
        $page = new Page($data);
        $this->renderer->render($page, $this->pages);
    }

}
