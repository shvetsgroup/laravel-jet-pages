<?php

namespace ShvetsGroup\Tests\JetPages\PageBuilder\Renderers;

use ShvetsGroup\JetPages\Page\PageCollection;
use ShvetsGroup\JetPages\PageBuilder\Renderers\EscapePreTagRenderer;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class EscapePreTagRendererTest extends AbstractTestCase
{
    /**
     * @var EscapePreTagRenderer
     */
    private $renderer;

    public function setUp(): void
    {
        parent::setUp();
        $this->renderer = new EscapePreTagRenderer();
    }

    /**
     * @dataProvider noMetaData
     */
    public function testRender($src, $expected)
    {
        $data = ['slug' => 'test', 'content' => $src];
        $pages = new PageCollection();
        $page = $pages->addNewPage($data);
        $this->renderer->render($page, $pages);
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
