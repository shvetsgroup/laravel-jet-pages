<?php namespace ShvetsGroup\Tests\JetPages\Middleware;

use ShvetsGroup\JetPages\Page\PageRegistry;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class StaticCacheTest extends AbstractTestCase
{
    /**
     * @var PageRegistry
     */
    private $pages;

    public function setUp()
    {
        parent::setUp();
        $this->pages = app()->make('pages');
        $this->deleteDir(public_path('cache'));
    }

    public function deleteDir($path)
    {
        if (!file_exists($path)) {
            return false;
        }
        return is_file($path) ?
            @unlink($path) :
            array_map([$this, 'deleteDir'], glob($path.'/*')) == @rmdir($path);
    }

    public function testCacheHTML()
    {
        $this->pages->createAndSave(['slug' => 'index']);
        $this->get('/')->assertStatus(200);
        $this->assertFileExists(public_path('cache/index.html'));

        $this->pages->createAndSave(['slug' => 'test']);
        $this->get('/test')->assertStatus(200);
        $this->assertFileExists(public_path('cache/test/index.html'));

        $this->pages->createAndSave(['slug' => 'test/test']);
        $this->get('/test/test')->assertStatus(200);
        $path = public_path('cache/test/test/index.html');
        $this->assertFileExists($path);
        $this->assertContains('<!-- Cached on ', file_get_contents($path));
    }

    public function testCacheSitemap()
    {
        $this->pages->createAndSave(['slug' => 'index']);
        $this->get('/sitemap.xml')->assertStatus(200);
        $path = public_path('cache/sitemap.xml');
        $this->assertFileExists($path);
        $this->assertNotContains('<!-- Cached on ', file_get_contents($path));
    }

    public function testNoCache()
    {
        $this->pages->createAndSave(['slug' => 'index', 'cache' => false]);
        $this->get('/')->assertStatus(200);
        $this->assertFileNotExists(public_path('cache/index.html'));
    }
}
