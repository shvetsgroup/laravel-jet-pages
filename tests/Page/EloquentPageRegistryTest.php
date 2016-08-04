<?php namespace ShvetsGroup\Tests\JetPages\Page;

use ShvetsGroup\JetPages\Page\EloquentPageRegistry;

class EloquentPageRegistryTest extends AbstractPageRegistryTest
{
    protected $migrate = true;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app->config->set('jetpages.driver', 'database');
    }

    public function setUp()
    {
        parent::setUp();
        $this->registry = new EloquentPageRegistry();
        $this->registry->reset();
    }
}
