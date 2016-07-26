<?php namespace ShvetsGroup\Tests\JetPages\Page;

use ShvetsGroup\JetPages\Page\EloquentPage;

class EloquentPageTest extends AbstractPageTest
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
        $this->page = new EloquentPage([]);
    }
}
