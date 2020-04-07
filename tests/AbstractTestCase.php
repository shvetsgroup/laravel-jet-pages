<?php

namespace ShvetsGroup\Tests\JetPages;

use GrahamCampbell\TestBench\AbstractPackageTestCase;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use MadWeb\Robots\RobotsServiceProvider;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ShvetsGroup\JetPages\JetPagesServiceProvider;
use ShvetsGroup\JetPages\Page\Page;
use Watson\Sitemap\SitemapServiceProvider;

/**
 * This is the abstract test case class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
abstract class AbstractTestCase extends AbstractPackageTestCase
{
    use MockeryPHPUnitIntegration;
    use DatabaseMigrations;

    /**
     * Override in subclasses to run migrations on setUp.
     * @var bool
     */
    protected $migrate = false;

    /**
     * Setup the application environment.
     *
     * @param  Application  $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->config->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => __DIR__.'/test.sqlite',
        ]);
    }

    /**
     * Get the required service providers.
     *
     * @param  Application  $app
     *
     * @return string[]
     */
    protected function getRequiredServiceProviders($app)
    {
        return [
            SitemapServiceProvider::class,
            RobotsServiceProvider::class
        ];
    }

    /**
     * Get the service provider class.
     *
     * @param  Application  $app
     *
     * @return string
     */
    protected function getServiceProviderClass($app)
    {
        return JetPagesServiceProvider::class;
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        $files = new Filesystem();
        $files->deleteDirectory($this->getBasePath().'/storage/app/routes');
        $files->deleteDirectory($this->getBasePath().'/storage/app/menu');
        $files->deleteDirectory($this->getBasePath().'/storage/app/redirects');
        $files->deleteDirectory($this->getBasePath().'/storage/app/content_hash');

        parent::setUp();

        if ($this->migrate) {
            $this->artisan('migrate', [
                '--database' => 'sqlite',
                '--path' => realpath(__DIR__.'/../src/resources/migrations'),
            ]);
        }
        @unlink($this->getBasePath().'/resources/content');
    }

    /**
     * Link directory with test content.
     */
    public function linkFixtureContent()
    {
        @unlink($this->getBasePath().'/resources/content');
        symlink(__DIR__.'/fixture/resources/content', $this->getBasePath().'/resources/content');
    }

    /**
     * Ignore timestamps when comparing Pages.
     *
     * @param  array  $data
     * @param  Page  $page
     */
    public function assertPageEquals(array $data, Page $page)
    {
        $page_data = $page->toArray();
        unset($data['uri']);
        unset($page_data['uri']);
        unset($data['created_at']);
        unset($data['updated_at']);
        unset($page_data['created_at']);
        unset($page_data['updated_at']);
        $this->assertEquals($data, $page_data);
    }
}
