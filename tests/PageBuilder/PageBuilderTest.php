<?php

namespace ShvetsGroup\Tests\JetPages\PageBuilder;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;
use ShvetsGroup\JetPages\Page\PageQuery;
use ShvetsGroup\JetPages\PageBuilder\PageBuilder;
use ShvetsGroup\JetPages\PageBuilder\PageBuilderException;
use ShvetsGroup\JetPages\PageBuilder\Parsers\MetaInfoParser;
use ShvetsGroup\JetPages\PageBuilder\PostProcessors\RedirectsPostProcessor;
use ShvetsGroup\JetPages\PageBuilder\Scanners\PageScanner;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class PageBuilderTest extends AbstractTestCase
{
    /**
     * @var PageBuilder
     */
    private $builder;

    protected $migrate = true;

    public function setUp(): void
    {
        parent::setUp();
        $this->linkFixtureContent();
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->config->set('jetpages.content_scanners', []);
        $app->config->set('jetpages.content_parsers', []);
        $app->config->set('jetpages.content_renderers', []);
        $app->config->set('jetpages.content_post_processors', []);
        $this->builder = new PageBuilder();
    }

    /**
     * Test that registered scanner is run during the scan.
     */
    public function testBuild2()
    {
        $this->builder->registerScanner(PageScanner::class, 'pages');
        $this->builder->registerParser(MetaInfoParser::class);
        $this->builder->build();

        $this->assertEquals(4, PageQuery::count());
        $this->assertDatabaseHas('pages', ['locale' => 'en', 'slug' => 'index', 'localeSlug' => 'en/index', 'uri' => '/', 'url' => 'http://localhost', 'href' => '/']);
        $this->assertDatabaseHas('pages', ['localeSlug' => 'en/test/test', 'uri' => 'test/test', 'href' => '/test/test']);
        $this->assertDatabaseHas('pages', ['localeSlug' => 'en/test', 'uri' => 'test', 'href' => '/test']);
        $this->assertDatabaseHas('pages', ['localeSlug' => 'en/test', 'uri' => 'test', 'href' => '/test']);
    }

    /**
     * Test that registered scanner is run during the scan.
     */
    public function testBuild3()
    {
        $this->builder->registerScanner(PageScanner::class, 'pages');
        $this->builder->registerParser(MetaInfoParser::class);
        $this->builder->registerPostProcessor(RedirectsPostProcessor::class);
        $this->builder->build();

        $this->refreshApplication();

        $this->get('/')->assertStatus(200)->assertSee('Some **test** <i>content</i>.', false);
        $this->get('test/test')->assertStatus(200)->assertSee('Some **test** subdir <i>content</i>.', false);
        $this->get('a')->assertRedirect('b');

        $this->app = $this->createApplication(function () {
            config(['app.debug' => true]);
        });

        $this->get('/')->assertStatus(200)->assertSee('Some **test** <i>content</i>.', false);
        $this->get('test/test')->assertStatus(200)->assertSee('Some **test** subdir <i>content</i>.', false);
        $this->get('a')->assertRedirect('b');
    }

    public function testFindFilesErrorPathNotString()
    {
        $this->expectException(PageBuilderException::class);
        $this->builder->registerScanner(PageScanner::class, 123);
    }

    public function testFindFilesErrorPathNonExistent()
    {
        $this->expectException(PageBuilderException::class);
        $this->builder->registerScanner(PageScanner::class, ['123']);
    }

    /**
     * This is a hacky solution to pass some config before routes initialization.
     */
    public function createApplication($afterConfigFunc = null)
    {
        $app = $this->resolveApplication();

        $this->resolveApplicationBindings($app);
        $this->resolveApplicationExceptionHandler($app);
        $this->resolveApplicationCore($app);
        $this->resolveApplicationConfiguration($app);

        if ($afterConfigFunc) {
            call_user_func($afterConfigFunc);
        }

        $this->resolveApplicationHttpKernel($app);
        $this->resolveApplicationConsoleKernel($app);
        $this->resolveApplicationBootstrappers($app);

        return $app;
    }

    /**
     * Show timestamp of last updated page.
     */
    public function testContentHash()
    {
        Page::create(['slug' => 'a-page']);
        $this->assertEquals('cf8cd01a835a6bf047e8ff45ccb17639', $this->builder->getBuildHash());
    }
}
