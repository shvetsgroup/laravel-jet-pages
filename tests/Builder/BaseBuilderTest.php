<?php namespace ShvetsGroup\Tests\JetPages\Builders;

use Mockery;
use ShvetsGroup\JetPages\Builders\BaseBuilder;
use ShvetsGroup\JetPages\Builders\Parsers\MetaInfoParser;
use ShvetsGroup\JetPages\Builders\Scanners\PageScanner;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;
use function ShvetsGroup\JetPages\content_path;

class BaseBuilderTest extends AbstractTestCase
{
    /**
     * @var BaseBuilder
     */
    private $builder;

    public function setUp()
    {
        parent::setUp();
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
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
}
