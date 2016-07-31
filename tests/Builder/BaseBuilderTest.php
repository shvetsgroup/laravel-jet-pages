<?php namespace ShvetsGroup\Tests\JetPages\Builders;

use Mockery;
use ShvetsGroup\JetPages\Builders\BaseBuilder;
use ShvetsGroup\JetPages\Builders\Decorators\MetaInfoDecorator;
use ShvetsGroup\JetPages\Builders\Scanners\PageScanner;
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
        $app->config->set('jetpages.scanners', []);
        $this->builder = app()->make(BaseBuilder::class);
    }

    /**
     * Test that registed scanner is run during the scan.
     */
    public function testBuild()
    {
        $page = app()->make('page', [['slug' => 'slug', 'title' => 'test', 'content' => 'content']]);

        $scanner_mock = Mockery::mock(PageScanner::class)
            ->shouldReceive('scan')
            ->withAnyArgs()
            ->andReturn([$page])
            ->once()
            ->getMock();
        $this->app->instance(PageScanner::class, $scanner_mock);

        $decorator_mock = Mockery::mock(MetaInfoDecorator::class)
            ->shouldReceive('decorate')
            ->withAnyArgs()
            ->once()
            ->getMock();
        $this->app->instance(MetaInfoDecorator::class, $decorator_mock);

        $this->builder->registerScanner(PageScanner::class, content_path('pages'));
        $this->builder->registerDecorator(MetaInfoDecorator::class);
        $this->builder->build();
    }

    /**
     * @expectedException \ShvetsGroup\JetPages\Builders\ScannerPairIsInvalid
     */
    public function testFindFilesErrorPathNotString()
    {
        $this->builder->registerScanner(PageScanner::class, 123);
    }

    /**
     * @expectedException \ShvetsGroup\JetPages\Builders\ScannerPairIsInvalid
     */
    public function testFindFilesErrorPathNonExistent()
    {
        $this->builder->registerScanner(PageScanner::class, ['123']);
    }
}
