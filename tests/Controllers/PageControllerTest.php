<?php

namespace ShvetsGroup\Tests\JetPages\Controllers;

use ShvetsGroup\JetPages\Controllers\PageController;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class PageControllerTest extends AbstractTestCase
{
    /**
     * @var PageController
     */
    private $controller;

    public function setUp(): void
    {
        parent::setUp();

        app()->config->set('jetpages.content_scanners', []);
        app()->config->set('jetpages.content_parsers', []);
        app()->config->set('jetpages.content_renderers', []);
        app()->config->set('jetpages.content_post_processors', []);

        $this->controller = app()->make(PageController::class);
    }

    /**
     * Show an index page.
     */
    public function testIndex()
    {
        Page::create([
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
        Page::create([
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
}
