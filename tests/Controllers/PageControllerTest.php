<?php namespace ShvetsGroup\Tests\JetPages\Controllers;

use ShvetsGroup\JetPages\Page\Pagelike;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;
use ShvetsGroup\JetPages\Controllers\PageController;

class PageControllerTest extends AbstractTestCase
{
    /**
     * @var PageController
     */
    private $controller;

    /**
     * @var Pagelike
     */
    private $page;

    public function setUp()
    {
        parent::setUp();
        $this->controller = app()->make(PageController::class);
        $this->page = app()->make('page');
    }

    /**
     * Show an index page.
     */
    public function testIndex()
    {
        $this->page->createAndSave([
            'slug' => 'index',
            'title' => 'Test Index'
        ]);
        $this->visit('/')->seeStatusCode(200)->seeText('Test Index');
    }

    /**
     * Show existing non-index page.
     */
    public function testAPage()
    {
        $this->page->createAndSave([
            'slug' => 'a-page',
            'title' => 'Test title',
            'content' => 'Test content'
        ]);
        $this->visit('a-page')->seeStatusCode(200)->seeText('Test title')->seeText('Test content');
    }

    /**
     * Show 404 error when page is not defined.
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function test404()
    {
        $this->visit('/')->seeStatusCode(404);
        $this->visit('non-existing-page')->seeStatusCode(404);
    }

    /**
     * Show timestamp of last updated page.
     */
    public function testTimestamp()
    {
        $page = $this->page->createAndSave(['slug' => 'a-page']);
        $this->visit('ajax/jetpages/timestamp')->seeStatusCode(200)->seeJson(['timestamp' => $page->updated_at]);
    }
}
