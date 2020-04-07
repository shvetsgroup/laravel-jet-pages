<?php

namespace ShvetsGroup\Tests\JetPages\PageBuilder\Renderers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use ShvetsGroup\JetPages\Page\PageCollection;
use ShvetsGroup\JetPages\PageBuilder\Renderers\IncludeRenderer;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class IncludeRendererTest extends AbstractTestCase
{
    /**
     * @var IncludeRenderer
     */
    private $renderer;

    public function setUp(): void
    {
        parent::setUp();
        $this->linkFixtureContent();
        $this->renderer = new IncludeRenderer();
    }

    public function testRender()
    {
        $data = ['slug' => 'test', 'content' => "test\n!INCLUDE  \"includes/test.txt\"\ntest"];
        $pages = new PageCollection();
        $page = $pages->addNewPage($data);
        $this->renderer->render($page, $pages);
        $this->assertEquals("test\ntest\ninclude\ntest", $page->getAttribute('content'));
    }

    public function testIncludeDoesNotExist()
    {
        $this->expectException(FileNotFoundException::class);

        $data = ['slug' => 'test', 'content' => "test\n!INCLUDE  \"123\"\ntest"];
        $pages = new PageCollection();
        $page = $pages->addNewPage($data);
        $this->renderer->render($page, $pages);
    }

}
