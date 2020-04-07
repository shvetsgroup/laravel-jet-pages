<?php

namespace ShvetsGroup\Tests\JetPages\PageBuilder\Renderers;

use ShvetsGroup\JetPages\Page\PageCollection;
use ShvetsGroup\JetPages\PageBuilder\Renderers\MarkdownRenderer;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class MarkdownRendererTest extends AbstractTestCase
{
    /**
     * @var MarkdownRenderer
     */
    private $renderer;

    public function setUp(): void
    {
        parent::setUp();
        $this->renderer = new MarkdownRenderer();
    }

    public function testHTMLNotProcessed()
    {
        $data = [
            'extension' => 'html',
            'content' => '_test_',
        ];
        $pages = new PageCollection();
        $page = $pages->addNewPage($data);
        $this->renderer->render($page, $pages);
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
            'slug' => 'index',
            'extension' => 'md',
            'content' => $md,
        ];
        $pages = new PageCollection();
        $page = $pages->addNewPage($data);
        $this->renderer->render($page, $pages);
        $this->assertEquals($html, $page->getAttribute('content'));
    }

    public function testSimpleRenderAllContentFields()
    {
        $data = [
            'slug' => 'index',
            'extension' => 'md',
            'content' => 'test',
            'content_2' => 'test',
        ];
        $pages = new PageCollection();
        $page = $pages->addNewPage($data);
        $this->renderer->render($page, $pages);
        $this->assertEquals("<p>test</p>\n", $page->getAttribute('content'));
        $this->assertEquals("<p>test</p>\n", $page->getAttribute('content_2'));
    }

    public function testReferences()
    {
        $pages = new PageCollection();

        $dog = $pages->addNewPage([
            'slug' => 'dog',
            'title' => 'Dog',
            'extension' => 'md',
            'content' => 'This is a [dog](Dog). It does not like [cats][Cat]',
        ]);

        $cat = $pages->addNewPage([
            'slug' => 'cat',
            'title' => 'Cat',
            'extension' => 'md',
            'content' => 'This is a [Cat]. It does not like [dogs](/dog)',
        ]);

        $dog_ru = $pages->addNewPage([
            'locale' => 'ru',
            'slug' => 'dog',
            'title' => 'Собака',
            'extension' => 'md',
            'content' => 'Это [собака][Dog]. Да, [собаки][Собака] не любят [котов][Cat]',
        ]);

        $this->renderer->render($dog, $pages);
        $this->assertEquals('<p>This is a <a href="Dog">dog</a>. It does not like <a href="/cat" title="Cat">cats</a></p>'."\n", $dog->getAttribute('content'));

        $this->renderer->render($cat, $pages);
        $this->assertEquals('<p>This is a <a href="/cat" title="Cat">Cat</a>. It does not like <a href="/dog">dogs</a></p>'."\n", $cat->getAttribute('content'));

        $this->renderer->render($dog_ru, $pages);
        $this->assertEquals('<p>Это <a href="/ru/dog" title="Собака">собака</a>. Да, <a href="/ru/dog" title="Собака">собаки</a> не любят <a href="/cat" title="Cat">котов</a></p>'."\n", $dog_ru->getAttribute('content'));
    }
}
