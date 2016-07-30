<?php namespace ShvetsGroup\Tests\JetPages\Builders;

use Faker\Provider\Base;
use Mockery;
use ShvetsGroup\JetPages\Builders\BaseBuilder;
use ShvetsGroup\JetPages\Builders\Scanners\PageScanner;
use ShvetsGroup\JetPages\Page\Pagelike;
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
    public function testRegisterScanner()
    {
        $scanner_mock = Mockery::mock(PageScanner::class)
            ->shouldReceive('scan')
            ->once()
            ->andReturn([['slug' => 'slug', 'title' => 'test', 'content' => 'content']])
            ->getMock();
        $this->app->instance(PageScanner::class, $scanner_mock);

        $page_mock = Mockery::mock(Pagelike::class)
            ->shouldReceive('fill')
            ->once()
            ->andReturnSelf()
            ->shouldReceive('save')
            ->once()
            ->getMock();
        $this->app->instance(Pagelike::class, $page_mock);

        $this->builder->registerScanner(PageScanner::class, content_path('pages'));
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
