<?php namespace ShvetsGroup\Tests\JetPages;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use GrahamCampbell\TestBench\AbstractPackageTestCase;
use ShvetsGroup\JetPages\JetPagesServiceProvider;
use ShvetsGroup\JetPages\Page\Page;

/**
 * This is the abstract test case class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
abstract class AbstractTestCase extends AbstractPackageTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Override in subclasses to run migrations on setUp.
     * @var bool
     */
    protected $migrate = false;

    /**
     * Setup the application environment.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app->config->set('jetpages.driver', 'cache');
//        $app->config->set('database.default', 'mysql');
//        $app->config->set('database.connections.sqlite', [
//            'driver'   => 'mysql',
//            'host' => '127.0.0.1',
//            'port' => '3306',
//            'database' => 'pages',
//            'username'   => 'root',
//            'password'   => 'root',
//            'charset' => 'utf8',
//            'collation' => 'utf8_unicode_ci',
//            'prefix' => '',
//            'strict' => false,
//            'engine' => null,
//        ]);
    }

    /**
     * Get the service provider class.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @return string
     */
    protected function getServiceProviderClass($app)
    {
        return JetPagesServiceProvider::class;
    }

    public function setUp()
    {
        parent::setUp();

        if ($this->migrate) {
            $this->artisan('migrate', [
                '--database' => 'sqlite',
                '--realpath' => realpath(__DIR__ . '/../src/resources/migrations'),
            ]);
        }
        @link(__DIR__.'/fixture/resources/content', $this->getBasePath() . '/resources/content');
    }

    public function assertPageEquals(array $data, Page $page, $ignore_timestamps = true) {
        $page_data = $page->toArray();
        unset($data['created_at']);
        unset($data['updated_at']);
        unset($page_data['created_at']);
        unset($page_data['updated_at']);
        $this->assertEquals($data, $page_data);
    }
}
