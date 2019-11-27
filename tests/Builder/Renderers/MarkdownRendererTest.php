<?php

namespace ShvetsGroup\Tests\JetPages\Builders\Renderers;

use ShvetsGroup\JetPages\Builders\Renderers\MarkdownRenderer;
use ShvetsGroup\JetPages\Builders\Renderers\Renderer;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\SimplePageRegistry;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class MarkdownRendererTest extends AbstractTestCase
{
    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var SimplePageRegistry
     */
    private $pages;

    public function setUp(): void
    {
        parent::setUp();
        $this->renderer = new MarkdownRenderer();
        $this->pages = new SimplePageRegistry();
    }

    public function testHTMLNotProcessed()
    {
        $data = [
            'extension' => 'html',
            'content' => '_test_',
        ];
        $page = new Page($data);
        $this->renderer->render($page, $this->pages);
        $this->assertEquals("_test_", $page->getAttribute('content'));
    }

    public function testSimpleRender()
    {
        $md = <<<MD
# Title

Test <i>page</i> `code`.

```
if (a->b) {
    run();
}
```
MD;

        $html = <<<HTML
<h1>Title</h1>
<p>Test <i>page</i> <code>code</code>.</p>
<pre><code>if (a-&gt;b) {
    run();
}
</code></pre>

HTML;

        $data = [
            'extension' => 'md',
            'content' => $md,
        ];
        $page = new Page($data);
        $this->renderer->render($page, $this->pages);
        $this->assertEquals($html, $page->getAttribute('content'));
    }

    public function testSimpleRenderAllContentFields()
    {
        $data = [
            'extension' => 'md',
            'content' => 'test',
            'content_2' => 'test',
        ];
        $page = new Page($data);
        $this->renderer->render($page, $this->pages);
        $this->assertEquals("<p>test</p>\n", $page->getAttribute('content'));
        $this->assertEquals("<p>test</p>\n", $page->getAttribute('content_2'));
    }

    public function testReferences()
    {
        $dog = new Page([
            'slug' => 'dog',
            'title' => 'Dog',
            'extension' => 'md',
            'content' => 'This is a [dog](Dog). It does not like [cats][Cat]',
        ]);
        $this->pages->add($dog);

        $cat = new Page([
            'slug' => 'cat',
            'title' => 'Cat',
            'extension' => 'md',
            'content' => 'This is a [Cat]. It does not like [dogs](/dog)',
        ]);
        $this->pages->add($cat);

        $dog_ru = new Page([
            'locale' => 'ru',
            'slug' => 'dog',
            'title' => 'Собака',
            'extension' => 'md',
            'content' => 'Это [собака][Dog]. Да, [собаки][Собака] не любят [котов][Cat]',
        ]);
        $this->pages->add($dog_ru);

        $this->renderer->render($dog, $this->pages);
        $this->assertEquals('<p>This is a <a href="Dog">dog</a>. It does not like <a href="/cat" title="Cat">cats</a></p>'."\n", $dog->getAttribute('content'));

        $this->renderer->render($cat, $this->pages);
        $this->assertEquals('<p>This is a <a href="/cat" title="Cat">Cat</a>. It does not like <a href="/dog">dogs</a></p>'."\n", $cat->getAttribute('content'));

        $this->renderer->render($dog_ru, $this->pages);
        $this->assertEquals('<p>Это <a href="/ru/dog" title="Собака">собака</a>. Да, <a href="/ru/dog" title="Собака">собаки</a> не любят <a href="/cat" title="Cat">котов</a></p>'."\n", $dog_ru->getAttribute('content'));
    }
}
