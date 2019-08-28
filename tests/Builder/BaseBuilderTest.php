<?php

namespace ShvetsGroup\Tests\JetPages\Builders;

use Illuminate\Support\Facades\Artisan;
use Mockery;
use ShvetsGroup\JetPages\Builders\BaseBuilder;
use ShvetsGroup\JetPages\Builders\Parsers\MetaInfoParser;
use ShvetsGroup\JetPages\Builders\PostProcessors\RedirectsPostProcessor;
use ShvetsGroup\JetPages\Builders\Scanners\PageScanner;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class BaseBuilderTest extends AbstractTestCase
{
    /**
     * @var BaseBuilder
     */
    private $builder;

    public function setUp()
    {
        parent::setUp();
        $this->linkFixtureContent();
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app->config->set('jetpages.driver', 'database');
        $app->config->set('jetpages.content_scanners', []);
        $app->config->set('jetpages.content_parsers', []);
        $app->config->set('jetpages.content_renderers', []);
        $app->config->set('jetpages.content_post_processors', []);
        $this->builder = app()->make(BaseBuilder::class);
    }

    /**
     * Test that registed scanner is run during the scan.
     */
    public function testBuild()
    {
        $page = new Page(['locale' => 'en', 'slug' => 'slug', 'title' => 'test', 'content' => 'content']);

        $scanner_mock = Mockery::mock(PageScanner::class)
            ->shouldReceive('scanDirectory')
            ->withAnyArgs()
            ->andReturn([$page])
            ->once()
            ->getMock();
        $this->app->instance(PageScanner::class, $scanner_mock);

        $decorator_mock = Mockery::mock(MetaInfoParser::class)
            ->shouldReceive('parse')
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance(MetaInfoParser::class, $decorator_mock);

        $this->builder->registerScanner(PageScanner::class, 'pages');
        $this->builder->registerParser(MetaInfoParser::class);
        $this->builder->build();
    }

    /**
     * Test that registered scanner is run during the scan.
     */
    public function testBuild2()
    {
        $this->builder->registerScanner(PageScanner::class, 'pages');
        $this->builder->registerParser(MetaInfoParser::class);
        $this->builder->build();

        $this->refreshApplication();

        $this->get('/')->assertStatus(200)->assertSee('Some **test** <i>content</i>.');
        $this->get('test/test')->assertStatus(200)->assertSee('Some **test** subdir <i>content</i>.');
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

        $this->get('/')->assertStatus(200)->assertSee('Some **test** <i>content</i>.');
        $this->get('test/test')->assertStatus(200)->assertSee('Some **test** subdir <i>content</i>.');
        $this->get('a')->assertRedirect('b');

        $this->app = $this->createApplication(function(){
            config(['app.debug' => true]);
        });

        $this->get('/')->assertStatus(200)->assertSee('Some **test** <i>content</i>.');
        $this->get('test/test')->assertStatus(200)->assertSee('Some **test** subdir <i>content</i>.');
        $this->get('a')->assertRedirect('b');
    }

    /**
     * @expectedException \ShvetsGroup\JetPages\Builders\BuilderException
     */
    public function testFindFilesErrorPathNotString()
    {
        $this->builder->registerScanner(PageScanner::class, 123);
    }

    /**
     * @expectedException \ShvetsGroup\JetPages\Builders\BuilderException
     */
    public function testFindFilesErrorPathNonExistent()
    {
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
}
