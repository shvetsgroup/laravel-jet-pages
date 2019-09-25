<?php

namespace ShvetsGroup\Tests\JetPages\Controllers;

use ShvetsGroup\JetPages\Controllers\PageController;
use ShvetsGroup\JetPages\Page\PageRegistry;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class PageControllerTest extends AbstractTestCase
{
    /**
     * @var PageController
     */
    private $controller;

    /**
     * @var PageRegistry
     */
    private $pages;

    public function setUp()
    {
        parent::setUp();
        $this->controller = app()->make(PageController::class);
        $this->pages = app()->make('pages');
    }

    /**
     * Show an index page.
     */
    public function testIndex()
    {
        $this->pages->createAndSave([
            'slug' => 'index',
            'title' => 'Test Index',
        ]);
        $this->get('/')->assertStatus(200)->assertSee('Test Index');
    }

    /**
     * Show existing non-index page.
     */
    public function testAPage()
    {
        $this->pages->createAndSave([
            'slug' => 'a-page',
            'title' => 'Test title',
            'content' => 'Test content',
        ]);
        $this->get('a-page')->assertStatus(200)->assertSee('Test title')->assertSee('Test content');
    }

    /**
     * Show 404 error when page is not defined.
     */
    public function test404()
    {
        $this->get('/')->assertStatus(404);
        $this->get('non-existing-page')->assertStatus(404);
    }

    /**
     * Show timestamp of last updated page.
     */
    public function testTimestamp()
    {
        $page = $this->pages->createAndSave(['slug' => 'a-page']);
        $this->pages->updateBuildTime(0);
        $this->get('ajax/jetpages/timestamp')->assertStatus(200)->assertJson(['timestamp' => strtotime($page->updated_at)]);
    }
}
