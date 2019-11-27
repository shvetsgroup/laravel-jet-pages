<?php

namespace ShvetsGroup\Tests\JetPages\Builders\Renderers;

use ShvetsGroup\JetPages\Builders\Renderers\EscapePreTagRenderer;
use ShvetsGroup\JetPages\Builders\Renderers\Renderer;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\SimplePageRegistry;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class EscapePreTagRendererTest extends AbstractTestCase
{
    /**
     * @var Renderer
     */
    private $renderer;
    private $pages;

    public function setUp(): void
    {
        parent::setUp();
        $this->renderer = new EscapePreTagRenderer();
        $this->pages = new SimplePageRegistry();
    }

    /**
     * @dataProvider noMetaData
     */
    public function testRender($src, $expected)
    {
        $data = ['slug' => 'test', 'content' => $src];
        $page = new Page($data);
        $this->renderer->render($page, $this->pages);
        $this->assertEquals($expected, $page->getAttribute('content'));
    }

    public function noMetaData()
    {
        $code = "if (true &&\n 3 < 10)";
        $escaped = "if (true &amp;&amp;\n 3 &lt; 10)";
        return [
            ["<pre>$code</pre>", "<pre>$code</pre>"],
            ["<pre class=\"code\">$code</pre>", "<pre class=\"code\">$escaped</pre>"],
            ["<pre class=\"code java\" style=''>$code</pre>", "<pre class=\"code java\" style=''>$escaped</pre>"],
            ["<pre class=\"java code\" style=''>$code</pre>", "<pre class=\"java code\" style=''>$escaped</pre>"],
            ["<pre title='' class=\"code java\" style=''>$code</pre>", "<pre title='' class=\"code java\" style=''>$escaped</pre>"],
        ];
    }


}
