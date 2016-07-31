<?php namespace ShvetsGroup\Tests\JetPages\Builders\Scanners;

use ShvetsGroup\JetPages\Builders\Decorators\Decorator;
use ShvetsGroup\JetPages\Builders\Decorators\EscapePreTagDecorator;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class EscapePreTagDecoratorTest extends AbstractTestCase
{
    /**
     * @var Decorator
     */
    private $decorator;

    public function setUp()
    {
        parent::setUp();
        $this->decorator = new EscapePreTagDecorator();
    }

    /**
     * @dataProvider noMetaData
     */
    public function testDecorate($src, $expected)
    {
        $data = ['slug' => 'test', 'src' => $src];
        $page = app()->make('page', [$data]);
        $this->decorator->decorate($page);
        $this->assertEquals($expected, $page->getAttribute('src'));
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
